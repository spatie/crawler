<?php

namespace Spatie\Crawler;

use Generator;
use GuzzleHttp\Pool;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Collection;
use Symfony\Component\DomCrawler\Link;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DomCrawler\Crawler as DomCrawler;
use Tree\Node\Node;

class Crawler {

	/** @var \GuzzleHttp\Client */
	protected $client;

	/** @var \Spatie\Crawler\Url */
	protected $baseUrl;

	/** @var \Spatie\Crawler\CrawlObserver */
	protected $crawlObserver;

	/** @var \Spatie\Crawler\CrawlProfile */
	protected $crawlProfile;

	/** @var int */
	protected $concurrency;

	/** @var \Spatie\Crawler\CrawlQueue */
	protected $crawlQueue;

	/** @var int */
	protected $maximumDepth = 0;

	/** @var \Tree\Node\Node */
	protected $linkTree;

	/**
	 * @param array $clientOptions
	 *
	 * @return static
	 */
	public static function create(array $clientOptions = []) {

		$hasClientOpts = (bool)count($clientOptions);
		$client        = new Client($hasClientOpts ? $clientOptions : [
			RequestOptions::COOKIES         => true,
			RequestOptions::CONNECT_TIMEOUT => 10,
			RequestOptions::TIMEOUT         => 10,
			RequestOptions::ALLOW_REDIRECTS => false,
		]);

		return new static($client);
	}

	public function __construct(Client $client, int $concurrency = 10) {

		$this->client = $client;

		$this->concurrency = $concurrency;

		$this->crawlProfile = new CrawlAllUrls();

		$this->crawlQueue = new CrawlQueue();
	}

	/**
	 * @param int $concurrency
	 *
	 * @return $this
	 */
	public function setConcurrency(int $concurrency) {

		$this->concurrency = $concurrency;

		return $this;
	}

	/**
	 * @param int $maximumDepth
	 *
	 * @return $this
	 */
	public function setMaximumDepth(int $maximumDepth) {

		$this->maximumDepth = $maximumDepth;

		return $this;
	}

	/**
	 * @param \Spatie\Crawler\CrawlObserver $crawlObserver
	 *
	 * @return $this
	 */
	public function setCrawlObserver(CrawlObserver $crawlObserver) {

		$this->crawlObserver = $crawlObserver;

		return $this;
	}

	/**
	 * @param \Spatie\Crawler\CrawlProfile $crawlProfile
	 *
	 * @return $this
	 */
	public function setCrawlProfile(CrawlProfile $crawlProfile) {

		$this->crawlProfile = $crawlProfile;

		return $this;
	}

	/**
	 * @param \Spatie\Crawler\Url|string $baseUrl
	 */
	public function startCrawling($baseUrl) {

		if(!$baseUrl instanceof Url) {
			$baseUrl = Url::create($baseUrl);
		}

		$this->baseUrl = $baseUrl;

		$crawlUrl = CrawlUrl::create($baseUrl);

		$this->crawlQueue->add($crawlUrl);

		$this->linkTree = new Node((string)$this->baseUrl);

		$this->startCrawlingQueue();

		$this->crawlObserver->finishedCrawling();
	}

	protected function startCrawlingQueue() {

		while($this->crawlQueue->hasPendingUrls()) {
			$pool = new Pool($this->client, $this->getCrawlRequests(), [
				'concurrency' => $this->concurrency,
				'options'     => $this->client->getConfig(),
				'fulfilled'   => function(ResponseInterface $response, int $index) {

					$this->handleResponse($response, $index);

					$crawlUrl = $this->crawlQueue->getPendingUrlAtIndex($index);

					if($crawlUrl->url->host !== $this->baseUrl->host) {
						return;
					}

					$this->addAllLinksToCrawlQueue(
						(string)$response->getBody(),
						$crawlUrl = $this->crawlQueue->getPendingUrlAtIndex($index)->url
					);
				},
				'rejected'    => function(RequestException $exception, int $index) {

					$this->handleResponse($exception->getResponse(), $index);
				},
			]);

			$promise = $pool->promise();
			$promise->wait();

			$this->crawlQueue->removeProcessedUrlsFromPending();
		}
	}

	/**
	 * @param ResponseInterface|null $response
	 * @param int $index
	 */
	protected function handleResponse($response, int $index) {

		$crawlUrl = $this->crawlQueue->getPendingUrlAtIndex($index);

		$this->crawlObserver->hasBeenCrawled($crawlUrl->url, $response, $crawlUrl->foundOnUrl);
	}

	protected function getCrawlRequests(): Generator {

		$i = 0;
		while($crawlUrl = $this->crawlQueue->getPendingUrlAtIndex($i)) {
			if(!$this->crawlProfile->shouldCrawl($crawlUrl->url)) {
				$i++;
				continue;
			}

			if($this->crawlQueue->hasAlreadyBeenProcessed($crawlUrl)) {
				$i++;
				continue;
			}

			$this->crawlObserver->willCrawl($crawlUrl->url);

			$this->crawlQueue->markAsProcessed($crawlUrl);

			yield new Request('GET', (string)$crawlUrl->url);
			$i++;
		}
	}

	protected function addAllLinksToCrawlQueue(string $html, Url $foundOnUrl) {

		$allLinks = $this->extractAllLinks($html, $foundOnUrl);

		collect($allLinks)
			->filter(function(Url $url) {

				return $url->hasCrawlableScheme();
			})
			->map(function(Url $url) {

				return $this->normalizeUrl($url);
			})
			->filter(function(Url $url) {

				return $this->crawlProfile->shouldCrawl($url);
			})
			->reject(function($url) {

				return $this->crawlQueue->has($url);
			})
			->each(function(Url $url) use ($foundOnUrl) {


				$node = $this->addToLinkTree($this->linkTree, (string)$url, $foundOnUrl);

				if(($this->maximumDepth == 0) or ($node->getDepth() <= $this->maximumDepth)) {
					$this->crawlQueue->add(
						CrawlUrl::create($url, $foundOnUrl)
					);
				}
			});
	}

	protected function extractAllLinks(string $html, Url $foundOnUrl): Collection {

		$domCrawler = new DomCrawler($html, $foundOnUrl);

		return collect($domCrawler->filterXpath('//a')->links())
			->map(function(Link $link) {

				return Url::create($link->getUri());
			});
	}

	/**
	 * @param \Spatie\Crawler\Url $url
	 *
	 * @return \Spatie\Crawler\Url
	 */
	protected function normalizeUrl(Url $url): Url {

		return $url->removeFragment();
	}

	/**
	 * @param $node \Tree\Node\Node
	 * @param $url string
	 * @param $parentUrl string
	 */
	protected function addToLinkTree($node, string $url, string $parentUrl) {

		$returnNode = null;
		if($node->getValue() == $parentUrl) {
			$newNode = new Node($url);
			$node->addChild($newNode);

			return $newNode;
		}
		foreach($node->getChildren() as $currentNode) {
			$returnNode = $this->addToLinkTree($currentNode, $url, $parentUrl);
			if($returnNode != null) {
				break;
			}
		}

		return $returnNode;
	}

}