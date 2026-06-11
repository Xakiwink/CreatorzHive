const Utils={
  formatDate:(dateStr,format='medium')=>{const d=new Date(dateStr);if(Number.isNaN(d.getTime()))return '';return d.toLocaleDateString(undefined,format==='short'?{month:'short',day:'numeric'}:{year:'numeric',month:'short',day:'numeric'});},
  formatCurrency:(amount,currency='TZS')=>new Intl.NumberFormat(undefined,{style:'currency',currency,maximumFractionDigits:0}).format(Number(amount||0)),
  timeAgo:(dateStr)=>{const s=Math.floor((Date.now()-new Date(dateStr).getTime())/1000);const map=[[31536000,'year'],[2592000,'month'],[86400,'day'],[3600,'hour'],[60,'minute']];for(const [n,w] of map){const v=Math.floor(s/n);if(v>=1)return `${v} ${w}${v>1?'s':''} ago`;}return 'just now';},
  debounce:(fn,delay=300)=>{let t;return(...a)=>{clearTimeout(t);t=setTimeout(()=>fn(...a),delay);};},
  truncate:(str,l=100)=>String(str||'').length>l?String(str).slice(0,l-1)+'…':String(str||''),
  copyToClipboard:async(text)=>{if(navigator.clipboard?.writeText){await navigator.clipboard.writeText(text);return true;}const ta=document.createElement('textarea');ta.value=text;document.body.appendChild(ta);ta.select();const ok=document.execCommand('copy');ta.remove();return ok;},
  getQueryParam:(k)=>new URLSearchParams(window.location.search).get(k),
  pluralize:(count,word)=>`${count} ${word}${count===1?'':'s'}`,
  escapeHtml:(str)=>String(str||'').replace(/[&<>'"]/g,(c)=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[c]))
};
window.Utils=Utils;
