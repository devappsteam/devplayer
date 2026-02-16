<?php

namespace App\Modules\Stream\Controllers;

use App\Http\Controllers\Controller;
use App\Core\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;

class VideoProxyController extends Controller
{
    use ApiResponse;

    /**
     * Proxy de vídeo - permite acessar vídeos HTTP como HTTPS
     *
     * Uso: GET /api/v1/video-proxy?url=http://servidor/video.mp4
     */
    public function proxy(Request $request): Response
    {
        $videoUrl = $request->query('url');

        if (!$videoUrl) {
            return response('URL é obrigatória', 400);
        }

        // Validar se é uma URL válida
        if (!filter_var($videoUrl, FILTER_VALIDATE_URL)) {
            return response('URL inválida', 400);
        }

        // Apenas permitir protocolos HTTP e HTTPS
        $parsed = parse_url($videoUrl);
        if (!isset($parsed['scheme']) || !in_array($parsed['scheme'], ['http', 'https'])) {
            return response('Protocolo não permitido', 400);
        }

        try {
            // Fazer requisição para o vídeo com timeout
            $response = Http::timeout(30)
                ->withoutVerifying() // Ignore SSL verification se necessário
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'
                ])
                ->get($videoUrl, [
                    'stream' => true
                ]);

            if (!$response->successful()) {
                return response('Erro ao acessar vídeo: ' . $response->status(), $response->status());
            }

            // Obter informações do vídeo
            $contentType = $response->header('Content-Type') ?? 'video/mp4';
            $contentLength = $response->header('Content-Length');

            // Preparar headers de resposta
            $headers = [
                'Content-Type' => $contentType,
                'Accept-Ranges' => 'bytes',
                'Cache-Control' => 'public, max-age=3600',
                'Referrer-Policy' => 'no-referrer',
            ];

            if ($contentLength) {
                $headers['Content-Length'] = $contentLength;
            }

            // Se o cliente suporta Range requests
            if ($request->header('Range')) {
                $headers['Accept-Ranges'] = 'bytes';
            }

            return response(
                $response->getBody(),
                200,
                $headers
            );
        } catch (\Exception $e) {
            return response('Erro ao carregar vídeo: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Proxy de vídeo com streaming chunked
     * Melhor para vídeos grandes
     *
     * Uso: GET /api/v1/video-stream?url=http://servidor/video.mp4
     */
    public function stream(Request $request)
    {
        $videoUrl = $request->query('url');

        if (!$videoUrl) {
            return $this->errorResponse('URL é obrigatória', 400);
        }

        // Validar se é uma URL válida
        if (!filter_var($videoUrl, FILTER_VALIDATE_URL)) {
            return $this->errorResponse('URL inválida', 400);
        }

        // Apenas permitir protocolos HTTP e HTTPS
        $parsed = parse_url($videoUrl);
        if (!isset($parsed['scheme']) || !in_array($parsed['scheme'], ['http', 'https'])) {
            return $this->errorResponse('Protocolo não permitido', 400);
        }

        try {
            // Fazer requisição HEAD para obter metadados
            $headResponse = Http::timeout(10)
                ->withoutVerifying()
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'
                ])
                ->head($videoUrl);

            if (!$headResponse->successful()) {
                return $this->errorResponse('Erro ao acessar vídeo', $headResponse->status());
            }

            // Obter informações do vídeo
            $contentType = $headResponse->header('Content-Type') ?? 'video/mp4';
            $contentLength = $headResponse->header('Content-Length');

            // Retornar resposta com streaming
            return response()->stream(
                function () use ($videoUrl) {
                    $client = new \GuzzleHttp\Client();

                    try {
                        $response = $client->get($videoUrl, [
                            'timeout' => 30,
                            'verify' => false,
                            'headers' => [
                                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0'
                            ],
                            'stream' => true
                        ]);

                        $body = $response->getBody();
                        while (!$body->eof()) {
                            echo $body->read(8192); // Ler em chunks de 8KB
                            flush();
                        }
                    } catch (\Exception $e) {
                        // Erro durante o stream
                        abort(500, 'Erro ao fazer stream do vídeo');
                    }
                },
                200,
                [
                    'Content-Type' => $contentType,
                    'Accept-Ranges' => 'bytes',
                    'Cache-Control' => 'public, max-age=3600',
                    'Referrer-Policy' => 'no-referrer',
                    'Content-Length' => $contentLength ?? '',
                ]
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao carregar vídeo: ' . $e->getMessage(), 500);
        }
    }
}
