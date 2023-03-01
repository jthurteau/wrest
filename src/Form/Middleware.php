<?php

declare(strict_types=1);

namespace Saf\Form;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Mezzio\Template\TemplateRendererInterface;
use Mezzio\Plates\PlatesRenderer;
use Saf\Util\Layout;

abstract class Middleware
{

    abstract public function __construct(ServerRequestInterface $server);

    abstract public function view(); //#TODO PHP8 string or array

}