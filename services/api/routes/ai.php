<?php

use App\Mcp\Servers\ExpressSaleServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp/express-sale', ExpressSaleServer::class)
    ->middleware('auth:sanctum');
