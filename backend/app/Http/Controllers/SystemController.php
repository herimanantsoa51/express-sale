<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SystemController extends Controller
{
    public function getServerInfo(Request $request)
    {
        // Détecter l'IP du serveur
        $serverIp = $this->getLocalIP();
        
        return response()->json([
            'server_ip' => $serverIp,
            'server_url' => "http://{$serverIp}:3000",
            'api_url' => "http://{$serverIp}:8000/api",
            'server_name' => gethostname(),
            'network_interfaces' => $this->getAllIPs(),
        ]);
    }

    private function getLocalIP()
    {
        // Méthode 1: Via socket
        $sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        socket_connect($sock, "8.8.8.8", 53);
        socket_getsockname($sock, $name);
        socket_close($sock);
        
        if ($name && $name !== '0.0.0.0') {
            return $name;
        }

        // Méthode 2: Via shell
        $output = shell_exec("hostname -I | awk '{print $1}'");
        return trim($output) ?: '127.0.0.1';
    }

    private function getAllIPs()
    {
        $interfaces = [];
        $output = shell_exec("ip addr show | grep 'inet ' | awk '{print $2}' | cut -d/ -f1");
        
        if ($output) {
            $ips = explode("\n", trim($output));
            foreach ($ips as $ip) {
                if ($ip && $ip !== '127.0.0.1') {
                    $interfaces[] = $ip;
                }
            }
        }
        
        return $interfaces;
    }
}