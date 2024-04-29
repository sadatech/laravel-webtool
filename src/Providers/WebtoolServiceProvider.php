<?php
namespace Sadatech\Webtool\Providers;

use Illuminate\Support\ServiceProvider;
use Sadatech\Webtool\Console\Kernel as WebtoolConsoleKernel;

class WebtoolServiceProvider extends ServiceProvider
{
    use WebtoolConsoleKernel;
}