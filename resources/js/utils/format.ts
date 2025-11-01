export function fmtDMY(input: string | null | undefined): string {
  if (!input) return '-';
  // Try to match YYYY-MM-DD at the beginning
  const m = String(input).match(/^(\d{4})-(\d{2})-(\d{2})/);
  if (m) {
    return `${m[3]}/${m[2]}/${m[1]}`;
  }
  // Fallback: try Date parse and format DD/MM/YYYY
  const d = new Date(String(input));
  if (!isNaN(d.valueOf())) {
    const dd = String(d.getDate()).padStart(2, '0');
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const yy = d.getFullYear();
    return `${dd}/${mm}/${yy}`;
  }
  return String(input);
}
