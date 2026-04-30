<?php

use App\Mcp\Servers\JaraMarketServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::local('jaramarket', JaraMarketServer::class);
