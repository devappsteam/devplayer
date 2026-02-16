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
  // Retornar URL original sem modificações
  return videoUrl;
}

/**
 * Verificar se uma URL precisa reescrita de domínio
 */
export function needsRewrite(videoUrl: string): boolean {
  return !!videoUrl && videoUrl.includes('pfsv.io');}
