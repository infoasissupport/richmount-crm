{{-- Quotation document styles (.qdoc), shared by the builder's on-screen preview and the standalone document page --}}
<style>
.qdoc { font: 12px Inter, Arial, sans-serif; color: #111; background: #fff; max-width: 820px; margin: 0 auto; padding: 24px 28px; }
.qdoc table { width: 100%; border-collapse: collapse; margin-bottom: 0; }
.qdoc td, .qdoc th { border: 1px solid #444; padding: 5px 6px; vertical-align: middle; }
.qd-head { display: flex; justify-content: space-between; align-items: center; gap: 16px; border-bottom: 1.5px solid #333; padding-bottom: 10px; }
.qd-head img { width: 150px; height: auto; }
.qd-co { text-align: right; line-height: 1.45; }
.qd-co .ar { color: #941C3C; font-size: 16px; font-weight: 700; direction: rtl; }
.qd-co .en { color: #941C3C; font-size: 14px; font-weight: 600; }
.qd-co small { display: block; font-size: 11.5px; color: #222; }
.qd-title { text-align: center; font-size: 18px; font-weight: 700; letter-spacing: 0.5px; margin: 16px 0 10px; }
.qd-info td { font-size: 11px; width: 25%; }
.qd-info th { background: #F2F2F2; text-align: left; font-size: 11px; }
.qd-info td.k { font-weight: 700; }
.qd-proj { border: 1px solid #444; margin: 10px 0; padding: 6px 8px 16px 8px; font-weight: 700; font-size: 11px; }
.qd-items th { background: #F2F2F2; font-size: 11px; text-align: center; }
.qd-items td { font-size: 11px; }
.qd-items .c { text-align: center; }
.qd-items .r { text-align: right; font-weight: 700; white-space: nowrap; }
.qd-items td.d { width: 45%; } /* rich text from the line item editor; line breaks are <br> */
/* section heading row in the items table (e.g. PRELIMINARIES) */
.qd-items tr.sect td { color: #C00000; font-weight: 700; font-size: 13px; text-transform: uppercase; letter-spacing: .3px; padding: 8px 8px; background: #fff; }
/* line item photos (up to 3) under the description */
.qd-photos { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 6px; }
.qd-photos img { width: 78px; height: 58px; object-fit: cover; border: 1px solid #bbb; border-radius: 2px; background: #fff; }
.qd-items .sum td { background: #DDF3E3; font-weight: 700; }
.qd-items .grand td { background: #C9EDD3; font-weight: 700; }
.qd-items .words td { background: #DDF3E3; text-align: center; font-weight: 700; font-size: 11px; }
.qd-box { margin-top: 10px; }
.qd-box th { background: #DCE6F1; text-align: left; font-size: 11px; }
.qd-box td { font-size: 11px; line-height: 1.5; }
.qd-box p { margin: 0; white-space: pre-line; }
.qd-box .red { color: #C00000; font-weight: 700; }
.qd-box td.k { font-weight: 700; width: 40%; }
.qd-foot { margin-top: 24px; background: #941C3C; color: #fff; text-align: center; border-radius: 999px; padding: 6px 12px; font-size: 11.5px; }
</style>
