/**
 * Video Proxy Utility
 *
 * Permite acessar vídeos HTTP através de um proxy HTTPS
 * Útil quando o host do vídeo não usa HTTPS mas o navegador exige
 */

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api/v1';

export function getVideoProxyUrl(videoUrl: string): string {
  // Se a URL já é HTTPS, retornar como está
  if (videoUrl.startsWith('https://')) {
    return videoUrl;
  }

  // Se é HTTP, converter para proxy HTTPS
  if (videoUrl.startsWith('http://')) {
    const encodedUrl = encodeURIComponent(videoUrl);

    // Se API está em HTTPS, usar streaming
    if (API_BASE_URL.startsWith('https://')) {
      return `${API_BASE_URL}/video-stream?url=${encodedUrl}`;
    }

    // Se API está em HTTP, usar proxy direto
    return `${API_BASE_URL}/video-proxy?url=${encodedUrl}`;
  }

  // Se não tem protocolo, assumir HTTP e fazer proxy
  const fullUrl = `http://${videoUrl}`;
  const encodedUrl = encodeURIComponent(fullUrl);
  return `${API_BASE_URL}/video-proxy?url=${encodedUrl}`;
}

/**
 * Obter URL de vídeo segura para reprodução
 *
 * @param originalUrl URL original do vídeo (pode ser HTTP)
 * @param forceProxy Forçar uso do proxy mesmo para HTTPS
 * @returns URL segura para usar no player
 */
export function getSecureVideoUrl(originalUrl: string, forceProxy: boolean = false): string {
  if (!originalUrl) return '';

  // Se forçar proxy, sempre usar
  if (forceProxy) {
    const encodedUrl = encodeURIComponent(originalUrl);
    return `${API_BASE_URL}/video-stream?url=${encodedUrl}`;
  }

  // Caso contrário, usar proxy apenas para HTTP
  return getVideoProxyUrl(originalUrl);
}

/**
 * Verificar se uma URL precisa de proxy
 */
export function needsProxy(videoUrl: string): boolean {
  return !!videoUrl && !videoUrl.startsWith('https://');
}
