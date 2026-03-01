<?php

namespace Spatie\Crawler\Enums;

enum ResourceType: string
{
    case Link = 'link';
    case Image = 'image';
    case Script = 'script';
    case Stylesheet = 'stylesheet';
    case OpenGraphImage = 'open_graph_image';
}
