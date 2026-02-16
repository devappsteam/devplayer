/**
 * Video Rewrite Utility
 *
 * Reescreve URLs de vídeos para usar o domínio de proxy stream.devapps.com.br
 * O Apache está configurado para fazer proxy transparente dessas requisições
 */

/**
 * Reescrever domínio pfsv.io para stream.devapps.com.br
 * O Apache no servidor está configurado para fazer proxy de volta para pfsv.io
 */
export function getVideoUrl(videoUrl: string): string {
  if (!videoUrl) return '';

  // Reescrever pfsv.io para stream.devapps.com.br
  if (videoUrl.includes('pfsv.io')) {
    return videoUrl.replace(/https?:\/\/pfsv\.io/i, 'https://stream.devapps.com.br');
  }

  // Se já usa stream.devapps.com.br, retornar como está
  if (videoUrl.includes('stream.devapps.com.br')) {
    return videoUrl;
  }

  // Se é HTTPS, retornar como está
  if (videoUrl.startsWith('https://')) {
    return videoUrl;
  }

  // Se é HTTP puro, retornar como está (proxy via Apache)
  return videoUrl;
}

/**
 * Verificar se uma URL precisa reescrita de domínio
 */
export function needsRewrite(videoUrl: string): boolean {
  return !!videoUrl && videoUrl.includes('pfsv.io');}
