var Sj=Object.defineProperty;var Cj=A=>{throw TypeError(A)};var $t=(A,e,t)=>e in A?Sj(A,e,{enumerable:!0,configurable:!0,writable:!0,value:t}):A[e]=t;var It=(A,e)=>()=>(A&&(e=A(A=0)),e);var qt=(A,e)=>{for(var t in e)Sj(A,t,{get:e[t],enumerable:!0})};var nA=(A,e,t)=>$t(A,typeof e!="symbol"?e+"":e,t),Gt=(A,e,t)=>e.has(A)||Cj("Cannot "+t);var zj=(A,e,t)=>e.has(A)?Cj("Cannot add the same private member more than once"):e instanceof WeakSet?e.add(A):e.set(A,t);var sA=(A,e,t)=>(Gt(A,e,"access private method"),t);var ee={};qt(ee,{WasmCandidateWithPosition:()=>MA,WasmChangedContent:()=>TA,WasmScanner:()=>dj,default:()=>Ct,initSync:()=>St,wasm_init:()=>Dt});function PA(){return(vA===null||vA.byteLength===0)&&(vA=new Uint8Array(C.memory.buffer)),vA}function $A(A,e){return A=A>>>0,Zj.decode(PA().subarray(A,A+e))}function sj(A,e,t){if(t===void 0){let l=DA.encode(A),c=e(l.length,1)>>>0;return PA().subarray(c,c+l.length).set(l),IA=l.length,c}let j=A.length,r=e(j,1)>>>0,n=PA(),i=0;for(;i<j;i++){let l=A.charCodeAt(i);if(l>127)break;n[r+i]=l}if(i!==j){i!==0&&(A=A.slice(i)),r=t(r,j,j=i+A.length*3,1)>>>0;let l=PA().subarray(r+i,r+j),c=Pt(A,l);i+=c.written,r=t(r,j,i,1)>>>0}return IA=i,r}function lj(){return(mA===null||mA.buffer.detached===!0||mA.buffer.detached===void 0&&mA.buffer!==C.memory.buffer)&&(mA=new DataView(C.memory.buffer)),mA}function Dt(){C.wasm_init()}function Mt(A){return A==null}function Tt(A,e){if(!(A instanceof e))throw new Error(`expected instance of ${e.name}`)}function Ht(A,e){A=A>>>0;let t=lj(),j=[];for(let r=A;r<A+4*e;r+=4)j.push(C.__wbindgen_export_3.get(t.getUint32(r,!0)));return C.__externref_drop_slice(A,e),j}async function Lt(A,e){if(typeof Response=="function"&&A instanceof Response){if(typeof WebAssembly.instantiateStreaming=="function")try{return await WebAssembly.instantiateStreaming(A,e)}catch(j){if(A.headers.get("Content-Type")!="application/wasm")console.warn("`WebAssembly.instantiateStreaming` failed because your server does not serve Wasm with `application/wasm` MIME type. Falling back to `WebAssembly.instantiate` which is slower. Original error:\n",j);else throw j}let t=await A.arrayBuffer();return await WebAssembly.instantiate(t,e)}else{let t=await WebAssembly.instantiate(A,e);return t instanceof WebAssembly.Instance?{instance:t,module:A}:t}}function Qj(){let A={};return A.wbg={},A.wbg.__wbg_error_7534b8e9a36f1ab4=function(e,t){let j,r;try{j=e,r=t,console.error($A(e,t))}finally{C.__wbindgen_free(j,r,1)}},A.wbg.__wbg_new_8a6f238a6ece86ea=function(){return new Error},A.wbg.__wbg_stack_0ed75d68575b0f3c=function(e,t){let j=t.stack,r=sj(j,C.__wbindgen_malloc,C.__wbindgen_realloc),n=IA;lj().setInt32(e+4*1,n,!0),lj().setInt32(e+4*0,r,!0)},A.wbg.__wbg_wasmcandidatewithposition_new=function(e){return MA.__wrap(e)},A.wbg.__wbindgen_init_externref_table=function(){let e=C.__wbindgen_export_3,t=e.grow(4);e.set(0,void 0),e.set(t+0,void 0),e.set(t+1,null),e.set(t+2,!0),e.set(t+3,!1)},A.wbg.__wbindgen_throw=function(e,t){throw new Error($A(e,t))},A}function Ae(A,e){return C=A.exports,je.__wbindgen_wasm_module=e,mA=null,vA=null,C.__wbindgen_start(),C}function St(A){if(C!==void 0)return C;typeof A<"u"&&(Object.getPrototypeOf(A)===Object.prototype?{module:A}=A:console.warn("using deprecated parameters for `initSync()`; pass a single object instead"));let e=Qj();A instanceof WebAssembly.Module||(A=new WebAssembly.Module(A));let t=new WebAssembly.Instance(A,e);return Ae(t,A)}async function je(A){if(C!==void 0)return C;typeof A<"u"&&(Object.getPrototypeOf(A)===Object.prototype?{module_or_path:A}=A:console.warn("using deprecated parameters for the initialization function; pass a single object instead")),typeof A>"u"&&(A=new URL("tailwindcss_oxide_bg.wasm",import.meta.url));let e=Qj();(typeof A=="string"||typeof Request=="function"&&A instanceof Request||typeof URL=="function"&&A instanceof URL)&&(A=fetch(A));let{instance:t,module:j}=await Lt(await A,e);return Ae(t,j)}var C,Zj,vA,IA,DA,Pt,mA,Uj,MA,Wj,TA,Yj,dj,Ct,te=It(()=>{Zj=typeof TextDecoder<"u"?new TextDecoder("utf-8",{ignoreBOM:!0,fatal:!0}):{decode:()=>{throw Error("TextDecoder not available")}};typeof TextDecoder<"u"&&Zj.decode();vA=null;IA=0,DA=typeof TextEncoder<"u"?new TextEncoder("utf-8"):{encode:()=>{throw Error("TextEncoder not available")}},Pt=typeof DA.encodeInto=="function"?function(A,e){return DA.encodeInto(A,e)}:function(A,e){let t=DA.encode(A);return e.set(t),{read:A.length,written:t.length}};mA=null;Uj=typeof FinalizationRegistry>"u"?{register:()=>{},unregister:()=>{}}:new FinalizationRegistry(A=>C.__wbg_wasmcandidatewithposition_free(A>>>0,1)),MA=class A{static __wrap(e){e=e>>>0;let t=Object.create(A.prototype);return t.__wbg_ptr=e,Uj.register(t,t.__wbg_ptr,t),t}__destroy_into_raw(){let e=this.__wbg_ptr;return this.__wbg_ptr=0,Uj.unregister(this),e}free(){let e=this.__destroy_into_raw();C.__wbg_wasmcandidatewithposition_free(e,0)}get candidate(){let e,t;try{let j=C.wasmcandidatewithposition_candidate(this.__wbg_ptr);return e=j[0],t=j[1],$A(j[0],j[1])}finally{C.__wbindgen_free(e,t,1)}}get position(){return C.wasmcandidatewithposition_position(this.__wbg_ptr)>>>0}},Wj=typeof FinalizationRegistry>"u"?{register:()=>{},unregister:()=>{}}:new FinalizationRegistry(A=>C.__wbg_wasmchangedcontent_free(A>>>0,1)),TA=class{__destroy_into_raw(){let e=this.__wbg_ptr;return this.__wbg_ptr=0,Wj.unregister(this),e}free(){let e=this.__destroy_into_raw();C.__wbg_wasmchangedcontent_free(e,0)}constructor(e,t){var j=Mt(e)?0:sj(e,C.__wbindgen_malloc,C.__wbindgen_realloc),r=IA;let n=sj(t,C.__wbindgen_malloc,C.__wbindgen_realloc),i=IA,l=C.wasmchangedcontent_new(j,r,n,i);return this.__wbg_ptr=l>>>0,Wj.register(this,this.__wbg_ptr,this),this}get content(){let e=C.wasmchangedcontent_content(this.__wbg_ptr),t;return e[0]!==0&&(t=$A(e[0],e[1]).slice(),C.__wbindgen_free(e[0],e[1]*1,1)),t}get extension(){let e,t;try{let j=C.wasmchangedcontent_extension(this.__wbg_ptr);return e=j[0],t=j[1],$A(j[0],j[1])}finally{C.__wbindgen_free(e,t,1)}}},Yj=typeof FinalizationRegistry>"u"?{register:()=>{},unregister:()=>{}}:new FinalizationRegistry(A=>C.__wbg_wasmscanner_free(A>>>0,1)),dj=class{__destroy_into_raw(){let e=this.__wbg_ptr;return this.__wbg_ptr=0,Yj.unregister(this),e}free(){let e=this.__destroy_into_raw();C.__wbg_wasmscanner_free(e,0)}constructor(){let e=C.wasmscanner_new();return this.__wbg_ptr=e>>>0,Yj.register(this,this.__wbg_ptr,this),this}getCandidatesWithPositions(e){Tt(e,TA);var t=e.__destroy_into_raw();let j=C.wasmscanner_getCandidatesWithPositions(this.__wbg_ptr,t);var r=Ht(j[0],j[1]).slice();return C.__wbindgen_free(j[0],j[1]*4,4),r}};Ct=je});var Nj=`@layer theme, base, components, utilities;

@layer theme {
  @theme default {
    --font-sans:
      ui-sans-serif, system-ui, sans-serif, "Apple Color Emoji",
      "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
    --font-serif: ui-serif, Georgia, Cambria, "Times New Roman", Times, serif;
    --font-mono:
      ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono",
      "Courier New", monospace;

    --color-red-50: oklch(97.1% 0.013 17.38);
    --color-red-100: oklch(93.6% 0.032 17.717);
    --color-red-200: oklch(88.5% 0.062 18.334);
    --color-red-300: oklch(80.8% 0.114 19.571);
    --color-red-400: oklch(70.4% 0.191 22.216);
    --color-red-500: oklch(63.7% 0.237 25.331);
    --color-red-600: oklch(57.7% 0.245 27.325);
    --color-red-700: oklch(50.5% 0.213 27.518);
    --color-red-800: oklch(44.4% 0.177 26.899);
    --color-red-900: oklch(39.6% 0.141 25.723);
    --color-red-950: oklch(25.8% 0.092 26.042);

    --color-orange-50: oklch(98% 0.016 73.684);
    --color-orange-100: oklch(95.4% 0.038 75.164);
    --color-orange-200: oklch(90.1% 0.076 70.697);
    --color-orange-300: oklch(83.7% 0.128 66.29);
    --color-orange-400: oklch(75% 0.183 55.934);
    --color-orange-500: oklch(70.5% 0.213 47.604);
    --color-orange-600: oklch(64.6% 0.222 41.116);
    --color-orange-700: oklch(55.3% 0.195 38.402);
    --color-orange-800: oklch(47% 0.157 37.304);
    --color-orange-900: oklch(40.8% 0.123 38.172);
    --color-orange-950: oklch(26.6% 0.079 36.259);

    --color-amber-50: oklch(98.7% 0.022 95.277);
    --color-amber-100: oklch(96.2% 0.059 95.617);
    --color-amber-200: oklch(92.4% 0.12 95.746);
    --color-amber-300: oklch(87.9% 0.169 91.605);
    --color-amber-400: oklch(82.8% 0.189 84.429);
    --color-amber-500: oklch(76.9% 0.188 70.08);
    --color-amber-600: oklch(66.6% 0.179 58.318);
    --color-amber-700: oklch(55.5% 0.163 48.998);
    --color-amber-800: oklch(47.3% 0.137 46.201);
    --color-amber-900: oklch(41.4% 0.112 45.904);
    --color-amber-950: oklch(27.9% 0.077 45.635);

    --color-yellow-50: oklch(98.7% 0.026 102.212);
    --color-yellow-100: oklch(97.3% 0.071 103.193);
    --color-yellow-200: oklch(94.5% 0.129 101.54);
    --color-yellow-300: oklch(90.5% 0.182 98.111);
    --color-yellow-400: oklch(85.2% 0.199 91.936);
    --color-yellow-500: oklch(79.5% 0.184 86.047);
    --color-yellow-600: oklch(68.1% 0.162 75.834);
    --color-yellow-700: oklch(55.4% 0.135 66.442);
    --color-yellow-800: oklch(47.6% 0.114 61.907);
    --color-yellow-900: oklch(42.1% 0.095 57.708);
    --color-yellow-950: oklch(28.6% 0.066 53.813);

    --color-lime-50: oklch(98.6% 0.031 120.757);
    --color-lime-100: oklch(96.7% 0.067 122.328);
    --color-lime-200: oklch(93.8% 0.127 124.321);
    --color-lime-300: oklch(89.7% 0.196 126.665);
    --color-lime-400: oklch(84.1% 0.238 128.85);
    --color-lime-500: oklch(76.8% 0.233 130.85);
    --color-lime-600: oklch(64.8% 0.2 131.684);
    --color-lime-700: oklch(53.2% 0.157 131.589);
    --color-lime-800: oklch(45.3% 0.124 130.933);
    --color-lime-900: oklch(40.5% 0.101 131.063);
    --color-lime-950: oklch(27.4% 0.072 132.109);

    --color-green-50: oklch(98.2% 0.018 155.826);
    --color-green-100: oklch(96.2% 0.044 156.743);
    --color-green-200: oklch(92.5% 0.084 155.995);
    --color-green-300: oklch(87.1% 0.15 154.449);
    --color-green-400: oklch(79.2% 0.209 151.711);
    --color-green-500: oklch(72.3% 0.219 149.579);
    --color-green-600: oklch(62.7% 0.194 149.214);
    --color-green-700: oklch(52.7% 0.154 150.069);
    --color-green-800: oklch(44.8% 0.119 151.328);
    --color-green-900: oklch(39.3% 0.095 152.535);
    --color-green-950: oklch(26.6% 0.065 152.934);

    --color-emerald-50: oklch(97.9% 0.021 166.113);
    --color-emerald-100: oklch(95% 0.052 163.051);
    --color-emerald-200: oklch(90.5% 0.093 164.15);
    --color-emerald-300: oklch(84.5% 0.143 164.978);
    --color-emerald-400: oklch(76.5% 0.177 163.223);
    --color-emerald-500: oklch(69.6% 0.17 162.48);
    --color-emerald-600: oklch(59.6% 0.145 163.225);
    --color-emerald-700: oklch(50.8% 0.118 165.612);
    --color-emerald-800: oklch(43.2% 0.095 166.913);
    --color-emerald-900: oklch(37.8% 0.077 168.94);
    --color-emerald-950: oklch(26.2% 0.051 172.552);

    --color-teal-50: oklch(98.4% 0.014 180.72);
    --color-teal-100: oklch(95.3% 0.051 180.801);
    --color-teal-200: oklch(91% 0.096 180.426);
    --color-teal-300: oklch(85.5% 0.138 181.071);
    --color-teal-400: oklch(77.7% 0.152 181.912);
    --color-teal-500: oklch(70.4% 0.14 182.503);
    --color-teal-600: oklch(60% 0.118 184.704);
    --color-teal-700: oklch(51.1% 0.096 186.391);
    --color-teal-800: oklch(43.7% 0.078 188.216);
    --color-teal-900: oklch(38.6% 0.063 188.416);
    --color-teal-950: oklch(27.7% 0.046 192.524);

    --color-cyan-50: oklch(98.4% 0.019 200.873);
    --color-cyan-100: oklch(95.6% 0.045 203.388);
    --color-cyan-200: oklch(91.7% 0.08 205.041);
    --color-cyan-300: oklch(86.5% 0.127 207.078);
    --color-cyan-400: oklch(78.9% 0.154 211.53);
    --color-cyan-500: oklch(71.5% 0.143 215.221);
    --color-cyan-600: oklch(60.9% 0.126 221.723);
    --color-cyan-700: oklch(52% 0.105 223.128);
    --color-cyan-800: oklch(45% 0.085 224.283);
    --color-cyan-900: oklch(39.8% 0.07 227.392);
    --color-cyan-950: oklch(30.2% 0.056 229.695);

    --color-sky-50: oklch(97.7% 0.013 236.62);
    --color-sky-100: oklch(95.1% 0.026 236.824);
    --color-sky-200: oklch(90.1% 0.058 230.902);
    --color-sky-300: oklch(82.8% 0.111 230.318);
    --color-sky-400: oklch(74.6% 0.16 232.661);
    --color-sky-500: oklch(68.5% 0.169 237.323);
    --color-sky-600: oklch(58.8% 0.158 241.966);
    --color-sky-700: oklch(50% 0.134 242.749);
    --color-sky-800: oklch(44.3% 0.11 240.79);
    --color-sky-900: oklch(39.1% 0.09 240.876);
    --color-sky-950: oklch(29.3% 0.066 243.157);

    --color-blue-50: oklch(97% 0.014 254.604);
    --color-blue-100: oklch(93.2% 0.032 255.585);
    --color-blue-200: oklch(88.2% 0.059 254.128);
    --color-blue-300: oklch(80.9% 0.105 251.813);
    --color-blue-400: oklch(70.7% 0.165 254.624);
    --color-blue-500: oklch(62.3% 0.214 259.815);
    --color-blue-600: oklch(54.6% 0.245 262.881);
    --color-blue-700: oklch(48.8% 0.243 264.376);
    --color-blue-800: oklch(42.4% 0.199 265.638);
    --color-blue-900: oklch(37.9% 0.146 265.522);
    --color-blue-950: oklch(28.2% 0.091 267.935);

    --color-indigo-50: oklch(96.2% 0.018 272.314);
    --color-indigo-100: oklch(93% 0.034 272.788);
    --color-indigo-200: oklch(87% 0.065 274.039);
    --color-indigo-300: oklch(78.5% 0.115 274.713);
    --color-indigo-400: oklch(67.3% 0.182 276.935);
    --color-indigo-500: oklch(58.5% 0.233 277.117);
    --color-indigo-600: oklch(51.1% 0.262 276.966);
    --color-indigo-700: oklch(45.7% 0.24 277.023);
    --color-indigo-800: oklch(39.8% 0.195 277.366);
    --color-indigo-900: oklch(35.9% 0.144 278.697);
    --color-indigo-950: oklch(25.7% 0.09 281.288);

    --color-violet-50: oklch(96.9% 0.016 293.756);
    --color-violet-100: oklch(94.3% 0.029 294.588);
    --color-violet-200: oklch(89.4% 0.057 293.283);
    --color-violet-300: oklch(81.1% 0.111 293.571);
    --color-violet-400: oklch(70.2% 0.183 293.541);
    --color-violet-500: oklch(60.6% 0.25 292.717);
    --color-violet-600: oklch(54.1% 0.281 293.009);
    --color-violet-700: oklch(49.1% 0.27 292.581);
    --color-violet-800: oklch(43.2% 0.232 292.759);
    --color-violet-900: oklch(38% 0.189 293.745);
    --color-violet-950: oklch(28.3% 0.141 291.089);

    --color-purple-50: oklch(97.7% 0.014 308.299);
    --color-purple-100: oklch(94.6% 0.033 307.174);
    --color-purple-200: oklch(90.2% 0.063 306.703);
    --color-purple-300: oklch(82.7% 0.119 306.383);
    --color-purple-400: oklch(71.4% 0.203 305.504);
    --color-purple-500: oklch(62.7% 0.265 303.9);
    --color-purple-600: oklch(55.8% 0.288 302.321);
    --color-purple-700: oklch(49.6% 0.265 301.924);
    --color-purple-800: oklch(43.8% 0.218 303.724);
    --color-purple-900: oklch(38.1% 0.176 304.987);
    --color-purple-950: oklch(29.1% 0.149 302.717);

    --color-fuchsia-50: oklch(97.7% 0.017 320.058);
    --color-fuchsia-100: oklch(95.2% 0.037 318.852);
    --color-fuchsia-200: oklch(90.3% 0.076 319.62);
    --color-fuchsia-300: oklch(83.3% 0.145 321.434);
    --color-fuchsia-400: oklch(74% 0.238 322.16);
    --color-fuchsia-500: oklch(66.7% 0.295 322.15);
    --color-fuchsia-600: oklch(59.1% 0.293 322.896);
    --color-fuchsia-700: oklch(51.8% 0.253 323.949);
    --color-fuchsia-800: oklch(45.2% 0.211 324.591);
    --color-fuchsia-900: oklch(40.1% 0.17 325.612);
    --color-fuchsia-950: oklch(29.3% 0.136 325.661);

    --color-pink-50: oklch(97.1% 0.014 343.198);
    --color-pink-100: oklch(94.8% 0.028 342.258);
    --color-pink-200: oklch(89.9% 0.061 343.231);
    --color-pink-300: oklch(82.3% 0.12 346.018);
    --color-pink-400: oklch(71.8% 0.202 349.761);
    --color-pink-500: oklch(65.6% 0.241 354.308);
    --color-pink-600: oklch(59.2% 0.249 0.584);
    --color-pink-700: oklch(52.5% 0.223 3.958);
    --color-pink-800: oklch(45.9% 0.187 3.815);
    --color-pink-900: oklch(40.8% 0.153 2.432);
    --color-pink-950: oklch(28.4% 0.109 3.907);

    --color-rose-50: oklch(96.9% 0.015 12.422);
    --color-rose-100: oklch(94.1% 0.03 12.58);
    --color-rose-200: oklch(89.2% 0.058 10.001);
    --color-rose-300: oklch(81% 0.117 11.638);
    --color-rose-400: oklch(71.2% 0.194 13.428);
    --color-rose-500: oklch(64.5% 0.246 16.439);
    --color-rose-600: oklch(58.6% 0.253 17.585);
    --color-rose-700: oklch(51.4% 0.222 16.935);
    --color-rose-800: oklch(45.5% 0.188 13.697);
    --color-rose-900: oklch(41% 0.159 10.272);
    --color-rose-950: oklch(27.1% 0.105 12.094);

    --color-slate-50: oklch(98.4% 0.003 247.858);
    --color-slate-100: oklch(96.8% 0.007 247.896);
    --color-slate-200: oklch(92.9% 0.013 255.508);
    --color-slate-300: oklch(86.9% 0.022 252.894);
    --color-slate-400: oklch(70.4% 0.04 256.788);
    --color-slate-500: oklch(55.4% 0.046 257.417);
    --color-slate-600: oklch(44.6% 0.043 257.281);
    --color-slate-700: oklch(37.2% 0.044 257.287);
    --color-slate-800: oklch(27.9% 0.041 260.031);
    --color-slate-900: oklch(20.8% 0.042 265.755);
    --color-slate-950: oklch(12.9% 0.042 264.695);

    --color-gray-50: oklch(98.5% 0.002 247.839);
    --color-gray-100: oklch(96.7% 0.003 264.542);
    --color-gray-200: oklch(92.8% 0.006 264.531);
    --color-gray-300: oklch(87.2% 0.01 258.338);
    --color-gray-400: oklch(70.7% 0.022 261.325);
    --color-gray-500: oklch(55.1% 0.027 264.364);
    --color-gray-600: oklch(44.6% 0.03 256.802);
    --color-gray-700: oklch(37.3% 0.034 259.733);
    --color-gray-800: oklch(27.8% 0.033 256.848);
    --color-gray-900: oklch(21% 0.034 264.665);
    --color-gray-950: oklch(13% 0.028 261.692);

    --color-zinc-50: oklch(98.5% 0 0);
    --color-zinc-100: oklch(96.7% 0.001 286.375);
    --color-zinc-200: oklch(92% 0.004 286.32);
    --color-zinc-300: oklch(87.1% 0.006 286.286);
    --color-zinc-400: oklch(70.5% 0.015 286.067);
    --color-zinc-500: oklch(55.2% 0.016 285.938);
    --color-zinc-600: oklch(44.2% 0.017 285.786);
    --color-zinc-700: oklch(37% 0.013 285.805);
    --color-zinc-800: oklch(27.4% 0.006 286.033);
    --color-zinc-900: oklch(21% 0.006 285.885);
    --color-zinc-950: oklch(14.1% 0.005 285.823);

    --color-neutral-50: oklch(98.5% 0 0);
    --color-neutral-100: oklch(97% 0 0);
    --color-neutral-200: oklch(92.2% 0 0);
    --color-neutral-300: oklch(87% 0 0);
    --color-neutral-400: oklch(70.8% 0 0);
    --color-neutral-500: oklch(55.6% 0 0);
    --color-neutral-600: oklch(43.9% 0 0);
    --color-neutral-700: oklch(37.1% 0 0);
    --color-neutral-800: oklch(26.9% 0 0);
    --color-neutral-900: oklch(20.5% 0 0);
    --color-neutral-950: oklch(14.5% 0 0);

    --color-stone-50: oklch(98.5% 0.001 106.423);
    --color-stone-100: oklch(97% 0.001 106.424);
    --color-stone-200: oklch(92.3% 0.003 48.717);
    --color-stone-300: oklch(86.9% 0.005 56.366);
    --color-stone-400: oklch(70.9% 0.01 56.259);
    --color-stone-500: oklch(55.3% 0.013 58.071);
    --color-stone-600: oklch(44.4% 0.011 73.639);
    --color-stone-700: oklch(37.4% 0.01 67.558);
    --color-stone-800: oklch(26.8% 0.007 34.298);
    --color-stone-900: oklch(21.6% 0.006 56.043);
    --color-stone-950: oklch(14.7% 0.004 49.25);

    --color-black: #000;
    --color-white: #fff;

    --spacing: 0.25rem;

    --breakpoint-sm: 40rem;
    --breakpoint-md: 48rem;
    --breakpoint-lg: 64rem;
    --breakpoint-xl: 80rem;
    --breakpoint-2xl: 96rem;

    --container-3xs: 16rem;
    --container-2xs: 18rem;
    --container-xs: 20rem;
    --container-sm: 24rem;
    --container-md: 28rem;
    --container-lg: 32rem;
    --container-xl: 36rem;
    --container-2xl: 42rem;
    --container-3xl: 48rem;
    --container-4xl: 56rem;
    --container-5xl: 64rem;
    --container-6xl: 72rem;
    --container-7xl: 80rem;

    --text-xs: 0.75rem;
    --text-xs--line-height: calc(1 / 0.75);
    --text-sm: 0.875rem;
    --text-sm--line-height: calc(1.25 / 0.875);
    --text-base: 1rem;
    --text-base--line-height: calc(1.5 / 1);
    --text-lg: 1.125rem;
    --text-lg--line-height: calc(1.75 / 1.125);
    --text-xl: 1.25rem;
    --text-xl--line-height: calc(1.75 / 1.25);
    --text-2xl: 1.5rem;
    --text-2xl--line-height: calc(2 / 1.5);
    --text-3xl: 1.875rem;
    --text-3xl--line-height: calc(2.25 / 1.875);
    --text-4xl: 2.25rem;
    --text-4xl--line-height: calc(2.5 / 2.25);
    --text-5xl: 3rem;
    --text-5xl--line-height: 1;
    --text-6xl: 3.75rem;
    --text-6xl--line-height: 1;
    --text-7xl: 4.5rem;
    --text-7xl--line-height: 1;
    --text-8xl: 6rem;
    --text-8xl--line-height: 1;
    --text-9xl: 8rem;
    --text-9xl--line-height: 1;

    --font-weight-thin: 100;
    --font-weight-extralight: 200;
    --font-weight-light: 300;
    --font-weight-normal: 400;
    --font-weight-medium: 500;
    --font-weight-semibold: 600;
    --font-weight-bold: 700;
    --font-weight-extrabold: 800;
    --font-weight-black: 900;

    --tracking-tighter: -0.05em;
    --tracking-tight: -0.025em;
    --tracking-normal: 0em;
    --tracking-wide: 0.025em;
    --tracking-wider: 0.05em;
    --tracking-widest: 0.1em;

    --leading-tight: 1.25;
    --leading-snug: 1.375;
    --leading-normal: 1.5;
    --leading-relaxed: 1.625;
    --leading-loose: 2;

    --radius-xs: 0.125rem;
    --radius-sm: 0.25rem;
    --radius-md: 0.375rem;
    --radius-lg: 0.5rem;
    --radius-xl: 0.75rem;
    --radius-2xl: 1rem;
    --radius-3xl: 1.5rem;
    --radius-4xl: 2rem;

    --shadow-2xs: 0 1px rgb(0 0 0 / 0.05);
    --shadow-xs: 0 1px 2px 0 rgb(0 0 0 / 0.05);
    --shadow-sm: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
    --shadow-md:
      0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
    --shadow-lg:
      0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
    --shadow-xl:
      0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
    --shadow-2xl: 0 25px 50px -12px rgb(0 0 0 / 0.25);

    --inset-shadow-2xs: inset 0 1px rgb(0 0 0 / 0.05);
    --inset-shadow-xs: inset 0 1px 1px rgb(0 0 0 / 0.05);
    --inset-shadow-sm: inset 0 2px 4px rgb(0 0 0 / 0.05);

    --drop-shadow-xs: 0 1px 1px rgb(0 0 0 / 0.05);
    --drop-shadow-sm: 0 1px 2px rgb(0 0 0 / 0.15);
    --drop-shadow-md: 0 3px 3px rgb(0 0 0 / 0.12);
    --drop-shadow-lg: 0 4px 4px rgb(0 0 0 / 0.15);
    --drop-shadow-xl: 0 9px 7px rgb(0 0 0 / 0.1);
    --drop-shadow-2xl: 0 25px 25px rgb(0 0 0 / 0.15);

    --text-shadow-2xs: 0px 1px 0px rgb(0 0 0 / 0.15);
    --text-shadow-xs: 0px 1px 1px rgb(0 0 0 / 0.2);
    --text-shadow-sm:
      0px 1px 0px rgb(0 0 0 / 0.075), 0px 1px 1px rgb(0 0 0 / 0.075),
      0px 2px 2px rgb(0 0 0 / 0.075);
    --text-shadow-md:
      0px 1px 1px rgb(0 0 0 / 0.1), 0px 1px 2px rgb(0 0 0 / 0.1),
      0px 2px 4px rgb(0 0 0 / 0.1);
    --text-shadow-lg:
      0px 1px 2px rgb(0 0 0 / 0.1), 0px 3px 2px rgb(0 0 0 / 0.1),
      0px 4px 8px rgb(0 0 0 / 0.1);

    --ease-in: cubic-bezier(0.4, 0, 1, 1);
    --ease-out: cubic-bezier(0, 0, 0.2, 1);
    --ease-in-out: cubic-bezier(0.4, 0, 0.2, 1);

    --animate-spin: spin 1s linear infinite;
    --animate-ping: ping 1s cubic-bezier(0, 0, 0.2, 1) infinite;
    --animate-pulse: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    --animate-bounce: bounce 1s infinite;

    @keyframes spin {
      to {
        transform: rotate(360deg);
      }
    }

    @keyframes ping {
      75%,
      100% {
        transform: scale(2);
        opacity: 0;
      }
    }

    @keyframes pulse {
      50% {
        opacity: 0.5;
      }
    }

    @keyframes bounce {
      0%,
      100% {
        transform: translateY(-25%);
        animation-timing-function: cubic-bezier(0.8, 0, 1, 1);
      }

      50% {
        transform: none;
        animation-timing-function: cubic-bezier(0, 0, 0.2, 1);
      }
    }

    --blur-xs: 4px;
    --blur-sm: 8px;
    --blur-md: 12px;
    --blur-lg: 16px;
    --blur-xl: 24px;
    --blur-2xl: 40px;
    --blur-3xl: 64px;

    --perspective-dramatic: 100px;
    --perspective-near: 300px;
    --perspective-normal: 500px;
    --perspective-midrange: 800px;
    --perspective-distant: 1200px;

    --aspect-video: 16 / 9;

    --default-transition-duration: 150ms;
    --default-transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    --default-font-family: --theme(--font-sans, initial);
    --default-font-feature-settings: --theme(
      --font-sans--font-feature-settings,
      initial
    );
    --default-font-variation-settings: --theme(
      --font-sans--font-variation-settings,
      initial
    );
    --default-mono-font-family: --theme(--font-mono, initial);
    --default-mono-font-feature-settings: --theme(
      --font-mono--font-feature-settings,
      initial
    );
    --default-mono-font-variation-settings: --theme(
      --font-mono--font-variation-settings,
      initial
    );
  }

  /* Deprecated */
  @theme default inline reference {
    --blur: 8px;
    --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
    --shadow-inner: inset 0 2px 4px 0 rgb(0 0 0 / 0.05);
    --drop-shadow: 0 1px 2px rgb(0 0 0 / 0.1), 0 1px 1px rgb(0 0 0 / 0.06);
    --radius: 0.25rem;
    --max-width-prose: 65ch;
  }
}

@layer base {
  /*
  1. Prevent padding and border from affecting element width. (https://github.com/mozdevs/cssremedy/issues/4)
  2. Remove default margins and padding
  3. Reset all borders.
*/

  *,
  ::after,
  ::before,
  ::backdrop,
  ::file-selector-button {
    box-sizing: border-box; /* 1 */
    margin: 0; /* 2 */
    padding: 0; /* 2 */
    border: 0 solid; /* 3 */
  }

  /*
  1. Use a consistent sensible line-height in all browsers.
  2. Prevent adjustments of font size after orientation changes in iOS.
  3. Use a more readable tab size.
  4. Use the user's configured \`sans\` font-family by default.
  5. Use the user's configured \`sans\` font-feature-settings by default.
  6. Use the user's configured \`sans\` font-variation-settings by default.
  7. Disable tap highlights on iOS.
*/

  html,
  :host {
    line-height: 1.5; /* 1 */
    -webkit-text-size-adjust: 100%; /* 2 */
    tab-size: 4; /* 3 */
    font-family: --theme(
      --default-font-family,
      ui-sans-serif,
      system-ui,
      sans-serif,
      "Apple Color Emoji",
      "Segoe UI Emoji",
      "Segoe UI Symbol",
      "Noto Color Emoji"
    ); /* 4 */
    font-feature-settings: --theme(
      --default-font-feature-settings,
      normal
    ); /* 5 */
    font-variation-settings: --theme(
      --default-font-variation-settings,
      normal
    ); /* 6 */
    -webkit-tap-highlight-color: transparent; /* 7 */
  }

  /*
  1. Add the correct height in Firefox.
  2. Correct the inheritance of border color in Firefox. (https://bugzilla.mozilla.org/show_bug.cgi?id=190655)
  3. Reset the default border style to a 1px solid border.
*/

  hr {
    height: 0; /* 1 */
    color: inherit; /* 2 */
    border-top-width: 1px; /* 3 */
  }

  /*
  Add the correct text decoration in Chrome, Edge, and Safari.
*/

  abbr:where([title]) {
    -webkit-text-decoration: underline dotted;
    text-decoration: underline dotted;
  }

  /*
  Remove the default font size and weight for headings.
*/

  h1,
  h2,
  h3,
  h4,
  h5,
  h6 {
    font-size: inherit;
    font-weight: inherit;
  }

  /*
  Reset links to optimize for opt-in styling instead of opt-out.
*/

  a {
    color: inherit;
    -webkit-text-decoration: inherit;
    text-decoration: inherit;
  }

  /*
  Add the correct font weight in Edge and Safari.
*/

  b,
  strong {
    font-weight: bolder;
  }

  /*
  1. Use the user's configured \`mono\` font-family by default.
  2. Use the user's configured \`mono\` font-feature-settings by default.
  3. Use the user's configured \`mono\` font-variation-settings by default.
  4. Correct the odd \`em\` font sizing in all browsers.
*/

  code,
  kbd,
  samp,
  pre {
    font-family: --theme(
      --default-mono-font-family,
      ui-monospace,
      SFMono-Regular,
      Menlo,
      Monaco,
      Consolas,
      "Liberation Mono",
      "Courier New",
      monospace
    ); /* 1 */
    font-feature-settings: --theme(
      --default-mono-font-feature-settings,
      normal
    ); /* 2 */
    font-variation-settings: --theme(
      --default-mono-font-variation-settings,
      normal
    ); /* 3 */
    font-size: 1em; /* 4 */
  }

  /*
  Add the correct font size in all browsers.
*/

  small {
    font-size: 80%;
  }

  /*
  Prevent \`sub\` and \`sup\` elements from affecting the line height in all browsers.
*/

  sub,
  sup {
    font-size: 75%;
    line-height: 0;
    position: relative;
    vertical-align: baseline;
  }

  sub {
    bottom: -0.25em;
  }

  sup {
    top: -0.5em;
  }

  /*
  1. Remove text indentation from table contents in Chrome and Safari. (https://bugs.chromium.org/p/chromium/issues/detail?id=999088, https://bugs.webkit.org/show_bug.cgi?id=201297)
  2. Correct table border color inheritance in all Chrome and Safari. (https://bugs.chromium.org/p/chromium/issues/detail?id=935729, https://bugs.webkit.org/show_bug.cgi?id=195016)
  3. Remove gaps between table borders by default.
*/

  table {
    text-indent: 0; /* 1 */
    border-color: inherit; /* 2 */
    border-collapse: collapse; /* 3 */
  }

  /*
  Use the modern Firefox focus style for all focusable elements.
*/

  :-moz-focusring {
    outline: auto;
  }

  /*
  Add the correct vertical alignment in Chrome and Firefox.
*/

  progress {
    vertical-align: baseline;
  }

  /*
  Add the correct display in Chrome and Safari.
*/

  summary {
    display: list-item;
  }

  /*
  Make lists unstyled by default.
*/

  ol,
  ul,
  menu {
    list-style: none;
  }

  /*
  1. Make replaced elements \`display: block\` by default. (https://github.com/mozdevs/cssremedy/issues/14)
  2. Add \`vertical-align: middle\` to align replaced elements more sensibly by default. (https://github.com/jensimmons/cssremedy/issues/14#issuecomment-634934210)
      This can trigger a poorly considered lint error in some tools but is included by design.
*/

  img,
  svg,
  video,
  canvas,
  audio,
  iframe,
  embed,
  object {
    display: block; /* 1 */
    vertical-align: middle; /* 2 */
  }

  /*
  Constrain images and videos to the parent width and preserve their intrinsic aspect ratio. (https://github.com/mozdevs/cssremedy/issues/14)
*/

  img,
  video {
    max-width: 100%;
    height: auto;
  }

  /*
  1. Inherit font styles in all browsers.
  2. Remove border radius in all browsers.
  3. Remove background color in all browsers.
  4. Ensure consistent opacity for disabled states in all browsers.
*/

  button,
  input,
  select,
  optgroup,
  textarea,
  ::file-selector-button {
    font: inherit; /* 1 */
    font-feature-settings: inherit; /* 1 */
    font-variation-settings: inherit; /* 1 */
    letter-spacing: inherit; /* 1 */
    color: inherit; /* 1 */
    border-radius: 0; /* 2 */
    background-color: transparent; /* 3 */
    opacity: 1; /* 4 */
  }

  /*
  Restore default font weight.
*/

  :where(select:is([multiple], [size])) optgroup {
    font-weight: bolder;
  }

  /*
  Restore indentation.
*/

  :where(select:is([multiple], [size])) optgroup option {
    padding-inline-start: 20px;
  }

  /*
  Restore space after button.
*/

  ::file-selector-button {
    margin-inline-end: 4px;
  }

  /*
  Reset the default placeholder opacity in Firefox. (https://github.com/tailwindlabs/tailwindcss/issues/3300)
*/

  ::placeholder {
    opacity: 1;
  }

  /*
  Set the default placeholder color to a semi-transparent version of the current text color in browsers that do not
  crash when using \`color-mix(\u2026)\` with \`currentcolor\`. (https://github.com/tailwindlabs/tailwindcss/issues/17194)
*/

  @supports (not (-webkit-appearance: -apple-pay-button)) /* Not Safari */ or
    (contain-intrinsic-size: 1px) /* Safari 17+ */ {
    ::placeholder {
      color: color-mix(in oklab, currentcolor 50%, transparent);
    }
  }

  /*
  Prevent resizing textareas horizontally by default.
*/

  textarea {
    resize: vertical;
  }

  /*
  Remove the inner padding in Chrome and Safari on macOS.
*/

  ::-webkit-search-decoration {
    -webkit-appearance: none;
  }

  /*
  1. Ensure date/time inputs have the same height when empty in iOS Safari.
  2. Ensure text alignment can be changed on date/time inputs in iOS Safari.
*/

  ::-webkit-date-and-time-value {
    min-height: 1lh; /* 1 */
    text-align: inherit; /* 2 */
  }

  /*
  Prevent height from changing on date/time inputs in macOS Safari when the input is set to \`display: block\`.
*/

  ::-webkit-datetime-edit {
    display: inline-flex;
  }

  /*
  Remove excess padding from pseudo-elements in date/time inputs to ensure consistent height across browsers.
*/

  ::-webkit-datetime-edit-fields-wrapper {
    padding: 0;
  }

  ::-webkit-datetime-edit,
  ::-webkit-datetime-edit-year-field,
  ::-webkit-datetime-edit-month-field,
  ::-webkit-datetime-edit-day-field,
  ::-webkit-datetime-edit-hour-field,
  ::-webkit-datetime-edit-minute-field,
  ::-webkit-datetime-edit-second-field,
  ::-webkit-datetime-edit-millisecond-field,
  ::-webkit-datetime-edit-meridiem-field {
    padding-block: 0;
  }

  /*
  Center dropdown marker shown on inputs with paired \`<datalist>\`s in Chrome. (https://github.com/tailwindlabs/tailwindcss/issues/18499)
*/

  ::-webkit-calendar-picker-indicator {
    line-height: 1;
  }

  /*
  Remove the additional \`:invalid\` styles in Firefox. (https://github.com/mozilla/gecko-dev/blob/2f9eacd9d3d995c937b4251a5557d95d494c9be1/layout/style/res/forms.css#L728-L737)
*/

  :-moz-ui-invalid {
    box-shadow: none;
  }

  /*
  Correct the inability to style the border radius in iOS Safari.
*/

  button,
  input:where([type="button"], [type="reset"], [type="submit"]),
  ::file-selector-button {
    appearance: button;
  }

  /*
  Correct the cursor style of increment and decrement buttons in Safari.
*/

  ::-webkit-inner-spin-button,
  ::-webkit-outer-spin-button {
    height: auto;
  }

  /*
  Make elements with the HTML hidden attribute stay hidden by default.
*/

  [hidden]:where(:not([hidden="until-found"])) {
    display: none !important;
  }
}

@layer utilities {
  @tailwind utilities;
}
`;var Rj=`/*
  1. Prevent padding and border from affecting element width. (https://github.com/mozdevs/cssremedy/issues/4)
  2. Remove default margins and padding
  3. Reset all borders.
*/

*,
::after,
::before,
::backdrop,
::file-selector-button {
  box-sizing: border-box; /* 1 */
  margin: 0; /* 2 */
  padding: 0; /* 2 */
  border: 0 solid; /* 3 */
}

/*
  1. Use a consistent sensible line-height in all browsers.
  2. Prevent adjustments of font size after orientation changes in iOS.
  3. Use a more readable tab size.
  4. Use the user's configured \`sans\` font-family by default.
  5. Use the user's configured \`sans\` font-feature-settings by default.
  6. Use the user's configured \`sans\` font-variation-settings by default.
  7. Disable tap highlights on iOS.
*/

html,
:host {
  line-height: 1.5; /* 1 */
  -webkit-text-size-adjust: 100%; /* 2 */
  tab-size: 4; /* 3 */
  font-family: --theme(
    --default-font-family,
    ui-sans-serif,
    system-ui,
    sans-serif,
    'Apple Color Emoji',
    'Segoe UI Emoji',
    'Segoe UI Symbol',
    'Noto Color Emoji'
  ); /* 4 */
  font-feature-settings: --theme(--default-font-feature-settings, normal); /* 5 */
  font-variation-settings: --theme(--default-font-variation-settings, normal); /* 6 */
  -webkit-tap-highlight-color: transparent; /* 7 */
}

/*
  1. Add the correct height in Firefox.
  2. Correct the inheritance of border color in Firefox. (https://bugzilla.mozilla.org/show_bug.cgi?id=190655)
  3. Reset the default border style to a 1px solid border.
*/

hr {
  height: 0; /* 1 */
  color: inherit; /* 2 */
  border-top-width: 1px; /* 3 */
}

/*
  Add the correct text decoration in Chrome, Edge, and Safari.
*/

abbr:where([title]) {
  -webkit-text-decoration: underline dotted;
  text-decoration: underline dotted;
}

/*
  Remove the default font size and weight for headings.
*/

h1,
h2,
h3,
h4,
h5,
h6 {
  font-size: inherit;
  font-weight: inherit;
}

/*
  Reset links to optimize for opt-in styling instead of opt-out.
*/

a {
  color: inherit;
  -webkit-text-decoration: inherit;
  text-decoration: inherit;
}

/*
  Add the correct font weight in Edge and Safari.
*/

b,
strong {
  font-weight: bolder;
}

/*
  1. Use the user's configured \`mono\` font-family by default.
  2. Use the user's configured \`mono\` font-feature-settings by default.
  3. Use the user's configured \`mono\` font-variation-settings by default.
  4. Correct the odd \`em\` font sizing in all browsers.
*/

code,
kbd,
samp,
pre {
  font-family: --theme(
    --default-mono-font-family,
    ui-monospace,
    SFMono-Regular,
    Menlo,
    Monaco,
    Consolas,
    'Liberation Mono',
    'Courier New',
    monospace
  ); /* 1 */
  font-feature-settings: --theme(--default-mono-font-feature-settings, normal); /* 2 */
  font-variation-settings: --theme(--default-mono-font-variation-settings, normal); /* 3 */
  font-size: 1em; /* 4 */
}

/*
  Add the correct font size in all browsers.
*/

small {
  font-size: 80%;
}

/*
  Prevent \`sub\` and \`sup\` elements from affecting the line height in all browsers.
*/

sub,
sup {
  font-size: 75%;
  line-height: 0;
  position: relative;
  vertical-align: baseline;
}

sub {
  bottom: -0.25em;
}

sup {
  top: -0.5em;
}

/*
  1. Remove text indentation from table contents in Chrome and Safari. (https://bugs.chromium.org/p/chromium/issues/detail?id=999088, https://bugs.webkit.org/show_bug.cgi?id=201297)
  2. Correct table border color inheritance in all Chrome and Safari. (https://bugs.chromium.org/p/chromium/issues/detail?id=935729, https://bugs.webkit.org/show_bug.cgi?id=195016)
  3. Remove gaps between table borders by default.
*/

table {
  text-indent: 0; /* 1 */
  border-color: inherit; /* 2 */
  border-collapse: collapse; /* 3 */
}

/*
  Use the modern Firefox focus style for all focusable elements.
*/

:-moz-focusring {
  outline: auto;
}

/*
  Add the correct vertical alignment in Chrome and Firefox.
*/

progress {
  vertical-align: baseline;
}

/*
  Add the correct display in Chrome and Safari.
*/

summary {
  display: list-item;
}

/*
  Make lists unstyled by default.
*/

ol,
ul,
menu {
  list-style: none;
}

/*
  1. Make replaced elements \`display: block\` by default. (https://github.com/mozdevs/cssremedy/issues/14)
  2. Add \`vertical-align: middle\` to align replaced elements more sensibly by default. (https://github.com/jensimmons/cssremedy/issues/14#issuecomment-634934210)
      This can trigger a poorly considered lint error in some tools but is included by design.
*/

img,
svg,
video,
canvas,
audio,
iframe,
embed,
object {
  display: block; /* 1 */
  vertical-align: middle; /* 2 */
}

/*
  Constrain images and videos to the parent width and preserve their intrinsic aspect ratio. (https://github.com/mozdevs/cssremedy/issues/14)
*/

img,
video {
  max-width: 100%;
  height: auto;
}

/*
  1. Inherit font styles in all browsers.
  2. Remove border radius in all browsers.
  3. Remove background color in all browsers.
  4. Ensure consistent opacity for disabled states in all browsers.
*/

button,
input,
select,
optgroup,
textarea,
::file-selector-button {
  font: inherit; /* 1 */
  font-feature-settings: inherit; /* 1 */
  font-variation-settings: inherit; /* 1 */
  letter-spacing: inherit; /* 1 */
  color: inherit; /* 1 */
  border-radius: 0; /* 2 */
  background-color: transparent; /* 3 */
  opacity: 1; /* 4 */
}

/*
  Restore default font weight.
*/

:where(select:is([multiple], [size])) optgroup {
  font-weight: bolder;
}

/*
  Restore indentation.
*/

:where(select:is([multiple], [size])) optgroup option {
  padding-inline-start: 20px;
}

/*
  Restore space after button.
*/

::file-selector-button {
  margin-inline-end: 4px;
}

/*
  Reset the default placeholder opacity in Firefox. (https://github.com/tailwindlabs/tailwindcss/issues/3300)
*/

::placeholder {
  opacity: 1;
}

/*
  Set the default placeholder color to a semi-transparent version of the current text color in browsers that do not
  crash when using \`color-mix(\u2026)\` with \`currentcolor\`. (https://github.com/tailwindlabs/tailwindcss/issues/17194)
*/

@supports (not (-webkit-appearance: -apple-pay-button)) /* Not Safari */ or
  (contain-intrinsic-size: 1px) /* Safari 17+ */ {
  ::placeholder {
    color: color-mix(in oklab, currentcolor 50%, transparent);
  }
}

/*
  Prevent resizing textareas horizontally by default.
*/

textarea {
  resize: vertical;
}

/*
  Remove the inner padding in Chrome and Safari on macOS.
*/

::-webkit-search-decoration {
  -webkit-appearance: none;
}

/*
  1. Ensure date/time inputs have the same height when empty in iOS Safari.
  2. Ensure text alignment can be changed on date/time inputs in iOS Safari.
*/

::-webkit-date-and-time-value {
  min-height: 1lh; /* 1 */
  text-align: inherit; /* 2 */
}

/*
  Prevent height from changing on date/time inputs in macOS Safari when the input is set to \`display: block\`.
*/

::-webkit-datetime-edit {
  display: inline-flex;
}

/*
  Remove excess padding from pseudo-elements in date/time inputs to ensure consistent height across browsers.
*/

::-webkit-datetime-edit-fields-wrapper {
  padding: 0;
}

::-webkit-datetime-edit,
::-webkit-datetime-edit-year-field,
::-webkit-datetime-edit-month-field,
::-webkit-datetime-edit-day-field,
::-webkit-datetime-edit-hour-field,
::-webkit-datetime-edit-minute-field,
::-webkit-datetime-edit-second-field,
::-webkit-datetime-edit-millisecond-field,
::-webkit-datetime-edit-meridiem-field {
  padding-block: 0;
}

/*
  Center dropdown marker shown on inputs with paired \`<datalist>\`s in Chrome. (https://github.com/tailwindlabs/tailwindcss/issues/18499)
*/

::-webkit-calendar-picker-indicator {
  line-height: 1;
}

/*
  Remove the additional \`:invalid\` styles in Firefox. (https://github.com/mozilla/gecko-dev/blob/2f9eacd9d3d995c937b4251a5557d95d494c9be1/layout/style/res/forms.css#L728-L737)
*/

:-moz-ui-invalid {
  box-shadow: none;
}

/*
  Correct the inability to style the border radius in iOS Safari.
*/

button,
input:where([type='button'], [type='reset'], [type='submit']),
::file-selector-button {
  appearance: button;
}

/*
  Correct the cursor style of increment and decrement buttons in Safari.
*/

::-webkit-inner-spin-button,
::-webkit-outer-spin-button {
  height: auto;
}

/*
  Make elements with the HTML hidden attribute stay hidden by default.
*/

[hidden]:where(:not([hidden='until-found'])) {
  display: none !important;
}
`;var Xj=`@theme default {
  --font-sans:
    ui-sans-serif, system-ui, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol',
    'Noto Color Emoji';
  --font-serif: ui-serif, Georgia, Cambria, 'Times New Roman', Times, serif;
  --font-mono:
    ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New',
    monospace;

  --color-red-50: oklch(97.1% 0.013 17.38);
  --color-red-100: oklch(93.6% 0.032 17.717);
  --color-red-200: oklch(88.5% 0.062 18.334);
  --color-red-300: oklch(80.8% 0.114 19.571);
  --color-red-400: oklch(70.4% 0.191 22.216);
  --color-red-500: oklch(63.7% 0.237 25.331);
  --color-red-600: oklch(57.7% 0.245 27.325);
  --color-red-700: oklch(50.5% 0.213 27.518);
  --color-red-800: oklch(44.4% 0.177 26.899);
  --color-red-900: oklch(39.6% 0.141 25.723);
  --color-red-950: oklch(25.8% 0.092 26.042);

  --color-orange-50: oklch(98% 0.016 73.684);
  --color-orange-100: oklch(95.4% 0.038 75.164);
  --color-orange-200: oklch(90.1% 0.076 70.697);
  --color-orange-300: oklch(83.7% 0.128 66.29);
  --color-orange-400: oklch(75% 0.183 55.934);
  --color-orange-500: oklch(70.5% 0.213 47.604);
  --color-orange-600: oklch(64.6% 0.222 41.116);
  --color-orange-700: oklch(55.3% 0.195 38.402);
  --color-orange-800: oklch(47% 0.157 37.304);
  --color-orange-900: oklch(40.8% 0.123 38.172);
  --color-orange-950: oklch(26.6% 0.079 36.259);

  --color-amber-50: oklch(98.7% 0.022 95.277);
  --color-amber-100: oklch(96.2% 0.059 95.617);
  --color-amber-200: oklch(92.4% 0.12 95.746);
  --color-amber-300: oklch(87.9% 0.169 91.605);
  --color-amber-400: oklch(82.8% 0.189 84.429);
  --color-amber-500: oklch(76.9% 0.188 70.08);
  --color-amber-600: oklch(66.6% 0.179 58.318);
  --color-amber-700: oklch(55.5% 0.163 48.998);
  --color-amber-800: oklch(47.3% 0.137 46.201);
  --color-amber-900: oklch(41.4% 0.112 45.904);
  --color-amber-950: oklch(27.9% 0.077 45.635);

  --color-yellow-50: oklch(98.7% 0.026 102.212);
  --color-yellow-100: oklch(97.3% 0.071 103.193);
  --color-yellow-200: oklch(94.5% 0.129 101.54);
  --color-yellow-300: oklch(90.5% 0.182 98.111);
  --color-yellow-400: oklch(85.2% 0.199 91.936);
  --color-yellow-500: oklch(79.5% 0.184 86.047);
  --color-yellow-600: oklch(68.1% 0.162 75.834);
  --color-yellow-700: oklch(55.4% 0.135 66.442);
  --color-yellow-800: oklch(47.6% 0.114 61.907);
  --color-yellow-900: oklch(42.1% 0.095 57.708);
  --color-yellow-950: oklch(28.6% 0.066 53.813);

  --color-lime-50: oklch(98.6% 0.031 120.757);
  --color-lime-100: oklch(96.7% 0.067 122.328);
  --color-lime-200: oklch(93.8% 0.127 124.321);
  --color-lime-300: oklch(89.7% 0.196 126.665);
  --color-lime-400: oklch(84.1% 0.238 128.85);
  --color-lime-500: oklch(76.8% 0.233 130.85);
  --color-lime-600: oklch(64.8% 0.2 131.684);
  --color-lime-700: oklch(53.2% 0.157 131.589);
  --color-lime-800: oklch(45.3% 0.124 130.933);
  --color-lime-900: oklch(40.5% 0.101 131.063);
  --color-lime-950: oklch(27.4% 0.072 132.109);

  --color-green-50: oklch(98.2% 0.018 155.826);
  --color-green-100: oklch(96.2% 0.044 156.743);
  --color-green-200: oklch(92.5% 0.084 155.995);
  --color-green-300: oklch(87.1% 0.15 154.449);
  --color-green-400: oklch(79.2% 0.209 151.711);
  --color-green-500: oklch(72.3% 0.219 149.579);
  --color-green-600: oklch(62.7% 0.194 149.214);
  --color-green-700: oklch(52.7% 0.154 150.069);
  --color-green-800: oklch(44.8% 0.119 151.328);
  --color-green-900: oklch(39.3% 0.095 152.535);
  --color-green-950: oklch(26.6% 0.065 152.934);

  --color-emerald-50: oklch(97.9% 0.021 166.113);
  --color-emerald-100: oklch(95% 0.052 163.051);
  --color-emerald-200: oklch(90.5% 0.093 164.15);
  --color-emerald-300: oklch(84.5% 0.143 164.978);
  --color-emerald-400: oklch(76.5% 0.177 163.223);
  --color-emerald-500: oklch(69.6% 0.17 162.48);
  --color-emerald-600: oklch(59.6% 0.145 163.225);
  --color-emerald-700: oklch(50.8% 0.118 165.612);
  --color-emerald-800: oklch(43.2% 0.095 166.913);
  --color-emerald-900: oklch(37.8% 0.077 168.94);
  --color-emerald-950: oklch(26.2% 0.051 172.552);

  --color-teal-50: oklch(98.4% 0.014 180.72);
  --color-teal-100: oklch(95.3% 0.051 180.801);
  --color-teal-200: oklch(91% 0.096 180.426);
  --color-teal-300: oklch(85.5% 0.138 181.071);
  --color-teal-400: oklch(77.7% 0.152 181.912);
  --color-teal-500: oklch(70.4% 0.14 182.503);
  --color-teal-600: oklch(60% 0.118 184.704);
  --color-teal-700: oklch(51.1% 0.096 186.391);
  --color-teal-800: oklch(43.7% 0.078 188.216);
  --color-teal-900: oklch(38.6% 0.063 188.416);
  --color-teal-950: oklch(27.7% 0.046 192.524);

  --color-cyan-50: oklch(98.4% 0.019 200.873);
  --color-cyan-100: oklch(95.6% 0.045 203.388);
  --color-cyan-200: oklch(91.7% 0.08 205.041);
  --color-cyan-300: oklch(86.5% 0.127 207.078);
  --color-cyan-400: oklch(78.9% 0.154 211.53);
  --color-cyan-500: oklch(71.5% 0.143 215.221);
  --color-cyan-600: oklch(60.9% 0.126 221.723);
  --color-cyan-700: oklch(52% 0.105 223.128);
  --color-cyan-800: oklch(45% 0.085 224.283);
  --color-cyan-900: oklch(39.8% 0.07 227.392);
  --color-cyan-950: oklch(30.2% 0.056 229.695);

  --color-sky-50: oklch(97.7% 0.013 236.62);
  --color-sky-100: oklch(95.1% 0.026 236.824);
  --color-sky-200: oklch(90.1% 0.058 230.902);
  --color-sky-300: oklch(82.8% 0.111 230.318);
  --color-sky-400: oklch(74.6% 0.16 232.661);
  --color-sky-500: oklch(68.5% 0.169 237.323);
  --color-sky-600: oklch(58.8% 0.158 241.966);
  --color-sky-700: oklch(50% 0.134 242.749);
  --color-sky-800: oklch(44.3% 0.11 240.79);
  --color-sky-900: oklch(39.1% 0.09 240.876);
  --color-sky-950: oklch(29.3% 0.066 243.157);

  --color-blue-50: oklch(97% 0.014 254.604);
  --color-blue-100: oklch(93.2% 0.032 255.585);
  --color-blue-200: oklch(88.2% 0.059 254.128);
  --color-blue-300: oklch(80.9% 0.105 251.813);
  --color-blue-400: oklch(70.7% 0.165 254.624);
  --color-blue-500: oklch(62.3% 0.214 259.815);
  --color-blue-600: oklch(54.6% 0.245 262.881);
  --color-blue-700: oklch(48.8% 0.243 264.376);
  --color-blue-800: oklch(42.4% 0.199 265.638);
  --color-blue-900: oklch(37.9% 0.146 265.522);
  --color-blue-950: oklch(28.2% 0.091 267.935);

  --color-indigo-50: oklch(96.2% 0.018 272.314);
  --color-indigo-100: oklch(93% 0.034 272.788);
  --color-indigo-200: oklch(87% 0.065 274.039);
  --color-indigo-300: oklch(78.5% 0.115 274.713);
  --color-indigo-400: oklch(67.3% 0.182 276.935);
  --color-indigo-500: oklch(58.5% 0.233 277.117);
  --color-indigo-600: oklch(51.1% 0.262 276.966);
  --color-indigo-700: oklch(45.7% 0.24 277.023);
  --color-indigo-800: oklch(39.8% 0.195 277.366);
  --color-indigo-900: oklch(35.9% 0.144 278.697);
  --color-indigo-950: oklch(25.7% 0.09 281.288);

  --color-violet-50: oklch(96.9% 0.016 293.756);
  --color-violet-100: oklch(94.3% 0.029 294.588);
  --color-violet-200: oklch(89.4% 0.057 293.283);
  --color-violet-300: oklch(81.1% 0.111 293.571);
  --color-violet-400: oklch(70.2% 0.183 293.541);
  --color-violet-500: oklch(60.6% 0.25 292.717);
  --color-violet-600: oklch(54.1% 0.281 293.009);
  --color-violet-700: oklch(49.1% 0.27 292.581);
  --color-violet-800: oklch(43.2% 0.232 292.759);
  --color-violet-900: oklch(38% 0.189 293.745);
  --color-violet-950: oklch(28.3% 0.141 291.089);

  --color-purple-50: oklch(97.7% 0.014 308.299);
  --color-purple-100: oklch(94.6% 0.033 307.174);
  --color-purple-200: oklch(90.2% 0.063 306.703);
  --color-purple-300: oklch(82.7% 0.119 306.383);
  --color-purple-400: oklch(71.4% 0.203 305.504);
  --color-purple-500: oklch(62.7% 0.265 303.9);
  --color-purple-600: oklch(55.8% 0.288 302.321);
  --color-purple-700: oklch(49.6% 0.265 301.924);
  --color-purple-800: oklch(43.8% 0.218 303.724);
  --color-purple-900: oklch(38.1% 0.176 304.987);
  --color-purple-950: oklch(29.1% 0.149 302.717);

  --color-fuchsia-50: oklch(97.7% 0.017 320.058);
  --color-fuchsia-100: oklch(95.2% 0.037 318.852);
  --color-fuchsia-200: oklch(90.3% 0.076 319.62);
  --color-fuchsia-300: oklch(83.3% 0.145 321.434);
  --color-fuchsia-400: oklch(74% 0.238 322.16);
  --color-fuchsia-500: oklch(66.7% 0.295 322.15);
  --color-fuchsia-600: oklch(59.1% 0.293 322.896);
  --color-fuchsia-700: oklch(51.8% 0.253 323.949);
  --color-fuchsia-800: oklch(45.2% 0.211 324.591);
  --color-fuchsia-900: oklch(40.1% 0.17 325.612);
  --color-fuchsia-950: oklch(29.3% 0.136 325.661);

  --color-pink-50: oklch(97.1% 0.014 343.198);
  --color-pink-100: oklch(94.8% 0.028 342.258);
  --color-pink-200: oklch(89.9% 0.061 343.231);
  --color-pink-300: oklch(82.3% 0.12 346.018);
  --color-pink-400: oklch(71.8% 0.202 349.761);
  --color-pink-500: oklch(65.6% 0.241 354.308);
  --color-pink-600: oklch(59.2% 0.249 0.584);
  --color-pink-700: oklch(52.5% 0.223 3.958);
  --color-pink-800: oklch(45.9% 0.187 3.815);
  --color-pink-900: oklch(40.8% 0.153 2.432);
  --color-pink-950: oklch(28.4% 0.109 3.907);

  --color-rose-50: oklch(96.9% 0.015 12.422);
  --color-rose-100: oklch(94.1% 0.03 12.58);
  --color-rose-200: oklch(89.2% 0.058 10.001);
  --color-rose-300: oklch(81% 0.117 11.638);
  --color-rose-400: oklch(71.2% 0.194 13.428);
  --color-rose-500: oklch(64.5% 0.246 16.439);
  --color-rose-600: oklch(58.6% 0.253 17.585);
  --color-rose-700: oklch(51.4% 0.222 16.935);
  --color-rose-800: oklch(45.5% 0.188 13.697);
  --color-rose-900: oklch(41% 0.159 10.272);
  --color-rose-950: oklch(27.1% 0.105 12.094);

  --color-slate-50: oklch(98.4% 0.003 247.858);
  --color-slate-100: oklch(96.8% 0.007 247.896);
  --color-slate-200: oklch(92.9% 0.013 255.508);
  --color-slate-300: oklch(86.9% 0.022 252.894);
  --color-slate-400: oklch(70.4% 0.04 256.788);
  --color-slate-500: oklch(55.4% 0.046 257.417);
  --color-slate-600: oklch(44.6% 0.043 257.281);
  --color-slate-700: oklch(37.2% 0.044 257.287);
  --color-slate-800: oklch(27.9% 0.041 260.031);
  --color-slate-900: oklch(20.8% 0.042 265.755);
  --color-slate-950: oklch(12.9% 0.042 264.695);

  --color-gray-50: oklch(98.5% 0.002 247.839);
  --color-gray-100: oklch(96.7% 0.003 264.542);
  --color-gray-200: oklch(92.8% 0.006 264.531);
  --color-gray-300: oklch(87.2% 0.01 258.338);
  --color-gray-400: oklch(70.7% 0.022 261.325);
  --color-gray-500: oklch(55.1% 0.027 264.364);
  --color-gray-600: oklch(44.6% 0.03 256.802);
  --color-gray-700: oklch(37.3% 0.034 259.733);
  --color-gray-800: oklch(27.8% 0.033 256.848);
  --color-gray-900: oklch(21% 0.034 264.665);
  --color-gray-950: oklch(13% 0.028 261.692);

  --color-zinc-50: oklch(98.5% 0 0);
  --color-zinc-100: oklch(96.7% 0.001 286.375);
  --color-zinc-200: oklch(92% 0.004 286.32);
  --color-zinc-300: oklch(87.1% 0.006 286.286);
  --color-zinc-400: oklch(70.5% 0.015 286.067);
  --color-zinc-500: oklch(55.2% 0.016 285.938);
  --color-zinc-600: oklch(44.2% 0.017 285.786);
  --color-zinc-700: oklch(37% 0.013 285.805);
  --color-zinc-800: oklch(27.4% 0.006 286.033);
  --color-zinc-900: oklch(21% 0.006 285.885);
  --color-zinc-950: oklch(14.1% 0.005 285.823);

  --color-neutral-50: oklch(98.5% 0 0);
  --color-neutral-100: oklch(97% 0 0);
  --color-neutral-200: oklch(92.2% 0 0);
  --color-neutral-300: oklch(87% 0 0);
  --color-neutral-400: oklch(70.8% 0 0);
  --color-neutral-500: oklch(55.6% 0 0);
  --color-neutral-600: oklch(43.9% 0 0);
  --color-neutral-700: oklch(37.1% 0 0);
  --color-neutral-800: oklch(26.9% 0 0);
  --color-neutral-900: oklch(20.5% 0 0);
  --color-neutral-950: oklch(14.5% 0 0);

  --color-stone-50: oklch(98.5% 0.001 106.423);
  --color-stone-100: oklch(97% 0.001 106.424);
  --color-stone-200: oklch(92.3% 0.003 48.717);
  --color-stone-300: oklch(86.9% 0.005 56.366);
  --color-stone-400: oklch(70.9% 0.01 56.259);
  --color-stone-500: oklch(55.3% 0.013 58.071);
  --color-stone-600: oklch(44.4% 0.011 73.639);
  --color-stone-700: oklch(37.4% 0.01 67.558);
  --color-stone-800: oklch(26.8% 0.007 34.298);
  --color-stone-900: oklch(21.6% 0.006 56.043);
  --color-stone-950: oklch(14.7% 0.004 49.25);

  --color-black: #000;
  --color-white: #fff;

  --spacing: 0.25rem;

  --breakpoint-sm: 40rem;
  --breakpoint-md: 48rem;
  --breakpoint-lg: 64rem;
  --breakpoint-xl: 80rem;
  --breakpoint-2xl: 96rem;

  --container-3xs: 16rem;
  --container-2xs: 18rem;
  --container-xs: 20rem;
  --container-sm: 24rem;
  --container-md: 28rem;
  --container-lg: 32rem;
  --container-xl: 36rem;
  --container-2xl: 42rem;
  --container-3xl: 48rem;
  --container-4xl: 56rem;
  --container-5xl: 64rem;
  --container-6xl: 72rem;
  --container-7xl: 80rem;

  --text-xs: 0.75rem;
  --text-xs--line-height: calc(1 / 0.75);
  --text-sm: 0.875rem;
  --text-sm--line-height: calc(1.25 / 0.875);
  --text-base: 1rem;
  --text-base--line-height: calc(1.5 / 1);
  --text-lg: 1.125rem;
  --text-lg--line-height: calc(1.75 / 1.125);
  --text-xl: 1.25rem;
  --text-xl--line-height: calc(1.75 / 1.25);
  --text-2xl: 1.5rem;
  --text-2xl--line-height: calc(2 / 1.5);
  --text-3xl: 1.875rem;
  --text-3xl--line-height: calc(2.25 / 1.875);
  --text-4xl: 2.25rem;
  --text-4xl--line-height: calc(2.5 / 2.25);
  --text-5xl: 3rem;
  --text-5xl--line-height: 1;
  --text-6xl: 3.75rem;
  --text-6xl--line-height: 1;
  --text-7xl: 4.5rem;
  --text-7xl--line-height: 1;
  --text-8xl: 6rem;
  --text-8xl--line-height: 1;
  --text-9xl: 8rem;
  --text-9xl--line-height: 1;

  --font-weight-thin: 100;
  --font-weight-extralight: 200;
  --font-weight-light: 300;
  --font-weight-normal: 400;
  --font-weight-medium: 500;
  --font-weight-semibold: 600;
  --font-weight-bold: 700;
  --font-weight-extrabold: 800;
  --font-weight-black: 900;

  --tracking-tighter: -0.05em;
  --tracking-tight: -0.025em;
  --tracking-normal: 0em;
  --tracking-wide: 0.025em;
  --tracking-wider: 0.05em;
  --tracking-widest: 0.1em;

  --leading-tight: 1.25;
  --leading-snug: 1.375;
  --leading-normal: 1.5;
  --leading-relaxed: 1.625;
  --leading-loose: 2;

  --radius-xs: 0.125rem;
  --radius-sm: 0.25rem;
  --radius-md: 0.375rem;
  --radius-lg: 0.5rem;
  --radius-xl: 0.75rem;
  --radius-2xl: 1rem;
  --radius-3xl: 1.5rem;
  --radius-4xl: 2rem;

  --shadow-2xs: 0 1px rgb(0 0 0 / 0.05);
  --shadow-xs: 0 1px 2px 0 rgb(0 0 0 / 0.05);
  --shadow-sm: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
  --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
  --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
  --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
  --shadow-2xl: 0 25px 50px -12px rgb(0 0 0 / 0.25);

  --inset-shadow-2xs: inset 0 1px rgb(0 0 0 / 0.05);
  --inset-shadow-xs: inset 0 1px 1px rgb(0 0 0 / 0.05);
  --inset-shadow-sm: inset 0 2px 4px rgb(0 0 0 / 0.05);

  --drop-shadow-xs: 0 1px 1px rgb(0 0 0 / 0.05);
  --drop-shadow-sm: 0 1px 2px rgb(0 0 0 / 0.15);
  --drop-shadow-md: 0 3px 3px rgb(0 0 0 / 0.12);
  --drop-shadow-lg: 0 4px 4px rgb(0 0 0 / 0.15);
  --drop-shadow-xl: 0 9px 7px rgb(0 0 0 / 0.1);
  --drop-shadow-2xl: 0 25px 25px rgb(0 0 0 / 0.15);

  --text-shadow-2xs: 0px 1px 0px rgb(0 0 0 / 0.15);
  --text-shadow-xs: 0px 1px 1px rgb(0 0 0 / 0.2);
  --text-shadow-sm:
    0px 1px 0px rgb(0 0 0 / 0.075), 0px 1px 1px rgb(0 0 0 / 0.075), 0px 2px 2px rgb(0 0 0 / 0.075);
  --text-shadow-md:
    0px 1px 1px rgb(0 0 0 / 0.1), 0px 1px 2px rgb(0 0 0 / 0.1), 0px 2px 4px rgb(0 0 0 / 0.1);
  --text-shadow-lg:
    0px 1px 2px rgb(0 0 0 / 0.1), 0px 3px 2px rgb(0 0 0 / 0.1), 0px 4px 8px rgb(0 0 0 / 0.1);

  --ease-in: cubic-bezier(0.4, 0, 1, 1);
  --ease-out: cubic-bezier(0, 0, 0.2, 1);
  --ease-in-out: cubic-bezier(0.4, 0, 0.2, 1);

  --animate-spin: spin 1s linear infinite;
  --animate-ping: ping 1s cubic-bezier(0, 0, 0.2, 1) infinite;
  --animate-pulse: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
  --animate-bounce: bounce 1s infinite;

  @keyframes spin {
    to {
      transform: rotate(360deg);
    }
  }

  @keyframes ping {
    75%,
    100% {
      transform: scale(2);
      opacity: 0;
    }
  }

  @keyframes pulse {
    50% {
      opacity: 0.5;
    }
  }

  @keyframes bounce {
    0%,
    100% {
      transform: translateY(-25%);
      animation-timing-function: cubic-bezier(0.8, 0, 1, 1);
    }

    50% {
      transform: none;
      animation-timing-function: cubic-bezier(0, 0, 0.2, 1);
    }
  }

  --blur-xs: 4px;
  --blur-sm: 8px;
  --blur-md: 12px;
  --blur-lg: 16px;
  --blur-xl: 24px;
  --blur-2xl: 40px;
  --blur-3xl: 64px;

  --perspective-dramatic: 100px;
  --perspective-near: 300px;
  --perspective-normal: 500px;
  --perspective-midrange: 800px;
  --perspective-distant: 1200px;

  --aspect-video: 16 / 9;

  --default-transition-duration: 150ms;
  --default-transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  --default-font-family: --theme(--font-sans, initial);
  --default-font-feature-settings: --theme(--font-sans--font-feature-settings, initial);
  --default-font-variation-settings: --theme(--font-sans--font-variation-settings, initial);
  --default-mono-font-family: --theme(--font-mono, initial);
  --default-mono-font-feature-settings: --theme(--font-mono--font-feature-settings, initial);
  --default-mono-font-variation-settings: --theme(--font-mono--font-variation-settings, initial);
}

/* Deprecated */
@theme default inline reference {
  --blur: 8px;
  --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
  --shadow-inner: inset 0 2px 4px 0 rgb(0 0 0 / 0.05);
  --drop-shadow: 0 1px 2px rgb(0 0 0 / 0.1), 0 1px 1px rgb(0 0 0 / 0.06);
  --radius: 0.25rem;
  --max-width-prose: 65ch;
}
`;var Vj=`@tailwind utilities;
`;async function nj(A,e){if(A==="tailwindcss")return{path:"virtual:tailwindcss/index.css",base:e,content:Nj};if(A==="tailwindcss/preflight"||A==="tailwindcss/preflight.css"||A==="./preflight.css")return{path:"virtual:tailwindcss/preflight.css",base:e,content:Rj};if(A==="tailwindcss/theme"||A==="tailwindcss/theme.css"||A==="./theme.css")return{path:"virtual:tailwindcss/theme.css",base:e,content:Xj};if(A==="tailwindcss/utilities"||A==="tailwindcss/utilities.css"||A==="./utilities.css")return{path:"virtual:tailwindcss/utilities.css",base:e,content:Vj};throw new Error(`External stylesheets not supported in browser build: "${A}"`)}var Jj="./tailwindcss_oxide_bg-Z6XZVLXW.wasm?url";async function fj({content:A="",returnPositions:e=!1,extension:t="html"}){let j,r;try{let c=await Promise.resolve().then(()=>(te(),ee));await c.default({module_or_path:new URL(Jj.replace("?url",""),import.meta.url)}),j=c.WasmScanner,r=c.WasmChangedContent}catch(c){throw new Error("Failed to load WASM scanner: "+c.message)}let n=new j,i=new r(A,t),l=n.getCandidatesWithPositions(i);return e?l:l.map(c=>c.candidate)}var HA={inherit:"inherit",current:"currentcolor",transparent:"transparent",black:"#000",white:"#fff",slate:{50:"oklch(98.4% 0.003 247.858)",100:"oklch(96.8% 0.007 247.896)",200:"oklch(92.9% 0.013 255.508)",300:"oklch(86.9% 0.022 252.894)",400:"oklch(70.4% 0.04 256.788)",500:"oklch(55.4% 0.046 257.417)",600:"oklch(44.6% 0.043 257.281)",700:"oklch(37.2% 0.044 257.287)",800:"oklch(27.9% 0.041 260.031)",900:"oklch(20.8% 0.042 265.755)",950:"oklch(12.9% 0.042 264.695)"},gray:{50:"oklch(98.5% 0.002 247.839)",100:"oklch(96.7% 0.003 264.542)",200:"oklch(92.8% 0.006 264.531)",300:"oklch(87.2% 0.01 258.338)",400:"oklch(70.7% 0.022 261.325)",500:"oklch(55.1% 0.027 264.364)",600:"oklch(44.6% 0.03 256.802)",700:"oklch(37.3% 0.034 259.733)",800:"oklch(27.8% 0.033 256.848)",900:"oklch(21% 0.034 264.665)",950:"oklch(13% 0.028 261.692)"},zinc:{50:"oklch(98.5% 0 0)",100:"oklch(96.7% 0.001 286.375)",200:"oklch(92% 0.004 286.32)",300:"oklch(87.1% 0.006 286.286)",400:"oklch(70.5% 0.015 286.067)",500:"oklch(55.2% 0.016 285.938)",600:"oklch(44.2% 0.017 285.786)",700:"oklch(37% 0.013 285.805)",800:"oklch(27.4% 0.006 286.033)",900:"oklch(21% 0.006 285.885)",950:"oklch(14.1% 0.005 285.823)"},neutral:{50:"oklch(98.5% 0 0)",100:"oklch(97% 0 0)",200:"oklch(92.2% 0 0)",300:"oklch(87% 0 0)",400:"oklch(70.8% 0 0)",500:"oklch(55.6% 0 0)",600:"oklch(43.9% 0 0)",700:"oklch(37.1% 0 0)",800:"oklch(26.9% 0 0)",900:"oklch(20.5% 0 0)",950:"oklch(14.5% 0 0)"},stone:{50:"oklch(98.5% 0.001 106.423)",100:"oklch(97% 0.001 106.424)",200:"oklch(92.3% 0.003 48.717)",300:"oklch(86.9% 0.005 56.366)",400:"oklch(70.9% 0.01 56.259)",500:"oklch(55.3% 0.013 58.071)",600:"oklch(44.4% 0.011 73.639)",700:"oklch(37.4% 0.01 67.558)",800:"oklch(26.8% 0.007 34.298)",900:"oklch(21.6% 0.006 56.043)",950:"oklch(14.7% 0.004 49.25)"},red:{50:"oklch(97.1% 0.013 17.38)",100:"oklch(93.6% 0.032 17.717)",200:"oklch(88.5% 0.062 18.334)",300:"oklch(80.8% 0.114 19.571)",400:"oklch(70.4% 0.191 22.216)",500:"oklch(63.7% 0.237 25.331)",600:"oklch(57.7% 0.245 27.325)",700:"oklch(50.5% 0.213 27.518)",800:"oklch(44.4% 0.177 26.899)",900:"oklch(39.6% 0.141 25.723)",950:"oklch(25.8% 0.092 26.042)"},orange:{50:"oklch(98% 0.016 73.684)",100:"oklch(95.4% 0.038 75.164)",200:"oklch(90.1% 0.076 70.697)",300:"oklch(83.7% 0.128 66.29)",400:"oklch(75% 0.183 55.934)",500:"oklch(70.5% 0.213 47.604)",600:"oklch(64.6% 0.222 41.116)",700:"oklch(55.3% 0.195 38.402)",800:"oklch(47% 0.157 37.304)",900:"oklch(40.8% 0.123 38.172)",950:"oklch(26.6% 0.079 36.259)"},amber:{50:"oklch(98.7% 0.022 95.277)",100:"oklch(96.2% 0.059 95.617)",200:"oklch(92.4% 0.12 95.746)",300:"oklch(87.9% 0.169 91.605)",400:"oklch(82.8% 0.189 84.429)",500:"oklch(76.9% 0.188 70.08)",600:"oklch(66.6% 0.179 58.318)",700:"oklch(55.5% 0.163 48.998)",800:"oklch(47.3% 0.137 46.201)",900:"oklch(41.4% 0.112 45.904)",950:"oklch(27.9% 0.077 45.635)"},yellow:{50:"oklch(98.7% 0.026 102.212)",100:"oklch(97.3% 0.071 103.193)",200:"oklch(94.5% 0.129 101.54)",300:"oklch(90.5% 0.182 98.111)",400:"oklch(85.2% 0.199 91.936)",500:"oklch(79.5% 0.184 86.047)",600:"oklch(68.1% 0.162 75.834)",700:"oklch(55.4% 0.135 66.442)",800:"oklch(47.6% 0.114 61.907)",900:"oklch(42.1% 0.095 57.708)",950:"oklch(28.6% 0.066 53.813)"},lime:{50:"oklch(98.6% 0.031 120.757)",100:"oklch(96.7% 0.067 122.328)",200:"oklch(93.8% 0.127 124.321)",300:"oklch(89.7% 0.196 126.665)",400:"oklch(84.1% 0.238 128.85)",500:"oklch(76.8% 0.233 130.85)",600:"oklch(64.8% 0.2 131.684)",700:"oklch(53.2% 0.157 131.589)",800:"oklch(45.3% 0.124 130.933)",900:"oklch(40.5% 0.101 131.063)",950:"oklch(27.4% 0.072 132.109)"},green:{50:"oklch(98.2% 0.018 155.826)",100:"oklch(96.2% 0.044 156.743)",200:"oklch(92.5% 0.084 155.995)",300:"oklch(87.1% 0.15 154.449)",400:"oklch(79.2% 0.209 151.711)",500:"oklch(72.3% 0.219 149.579)",600:"oklch(62.7% 0.194 149.214)",700:"oklch(52.7% 0.154 150.069)",800:"oklch(44.8% 0.119 151.328)",900:"oklch(39.3% 0.095 152.535)",950:"oklch(26.6% 0.065 152.934)"},emerald:{50:"oklch(97.9% 0.021 166.113)",100:"oklch(95% 0.052 163.051)",200:"oklch(90.5% 0.093 164.15)",300:"oklch(84.5% 0.143 164.978)",400:"oklch(76.5% 0.177 163.223)",500:"oklch(69.6% 0.17 162.48)",600:"oklch(59.6% 0.145 163.225)",700:"oklch(50.8% 0.118 165.612)",800:"oklch(43.2% 0.095 166.913)",900:"oklch(37.8% 0.077 168.94)",950:"oklch(26.2% 0.051 172.552)"},teal:{50:"oklch(98.4% 0.014 180.72)",100:"oklch(95.3% 0.051 180.801)",200:"oklch(91% 0.096 180.426)",300:"oklch(85.5% 0.138 181.071)",400:"oklch(77.7% 0.152 181.912)",500:"oklch(70.4% 0.14 182.503)",600:"oklch(60% 0.118 184.704)",700:"oklch(51.1% 0.096 186.391)",800:"oklch(43.7% 0.078 188.216)",900:"oklch(38.6% 0.063 188.416)",950:"oklch(27.7% 0.046 192.524)"},cyan:{50:"oklch(98.4% 0.019 200.873)",100:"oklch(95.6% 0.045 203.388)",200:"oklch(91.7% 0.08 205.041)",300:"oklch(86.5% 0.127 207.078)",400:"oklch(78.9% 0.154 211.53)",500:"oklch(71.5% 0.143 215.221)",600:"oklch(60.9% 0.126 221.723)",700:"oklch(52% 0.105 223.128)",800:"oklch(45% 0.085 224.283)",900:"oklch(39.8% 0.07 227.392)",950:"oklch(30.2% 0.056 229.695)"},sky:{50:"oklch(97.7% 0.013 236.62)",100:"oklch(95.1% 0.026 236.824)",200:"oklch(90.1% 0.058 230.902)",300:"oklch(82.8% 0.111 230.318)",400:"oklch(74.6% 0.16 232.661)",500:"oklch(68.5% 0.169 237.323)",600:"oklch(58.8% 0.158 241.966)",700:"oklch(50% 0.134 242.749)",800:"oklch(44.3% 0.11 240.79)",900:"oklch(39.1% 0.09 240.876)",950:"oklch(29.3% 0.066 243.157)"},blue:{50:"oklch(97% 0.014 254.604)",100:"oklch(93.2% 0.032 255.585)",200:"oklch(88.2% 0.059 254.128)",300:"oklch(80.9% 0.105 251.813)",400:"oklch(70.7% 0.165 254.624)",500:"oklch(62.3% 0.214 259.815)",600:"oklch(54.6% 0.245 262.881)",700:"oklch(48.8% 0.243 264.376)",800:"oklch(42.4% 0.199 265.638)",900:"oklch(37.9% 0.146 265.522)",950:"oklch(28.2% 0.091 267.935)"},indigo:{50:"oklch(96.2% 0.018 272.314)",100:"oklch(93% 0.034 272.788)",200:"oklch(87% 0.065 274.039)",300:"oklch(78.5% 0.115 274.713)",400:"oklch(67.3% 0.182 276.935)",500:"oklch(58.5% 0.233 277.117)",600:"oklch(51.1% 0.262 276.966)",700:"oklch(45.7% 0.24 277.023)",800:"oklch(39.8% 0.195 277.366)",900:"oklch(35.9% 0.144 278.697)",950:"oklch(25.7% 0.09 281.288)"},violet:{50:"oklch(96.9% 0.016 293.756)",100:"oklch(94.3% 0.029 294.588)",200:"oklch(89.4% 0.057 293.283)",300:"oklch(81.1% 0.111 293.571)",400:"oklch(70.2% 0.183 293.541)",500:"oklch(60.6% 0.25 292.717)",600:"oklch(54.1% 0.281 293.009)",700:"oklch(49.1% 0.27 292.581)",800:"oklch(43.2% 0.232 292.759)",900:"oklch(38% 0.189 293.745)",950:"oklch(28.3% 0.141 291.089)"},purple:{50:"oklch(97.7% 0.014 308.299)",100:"oklch(94.6% 0.033 307.174)",200:"oklch(90.2% 0.063 306.703)",300:"oklch(82.7% 0.119 306.383)",400:"oklch(71.4% 0.203 305.504)",500:"oklch(62.7% 0.265 303.9)",600:"oklch(55.8% 0.288 302.321)",700:"oklch(49.6% 0.265 301.924)",800:"oklch(43.8% 0.218 303.724)",900:"oklch(38.1% 0.176 304.987)",950:"oklch(29.1% 0.149 302.717)"},fuchsia:{50:"oklch(97.7% 0.017 320.058)",100:"oklch(95.2% 0.037 318.852)",200:"oklch(90.3% 0.076 319.62)",300:"oklch(83.3% 0.145 321.434)",400:"oklch(74% 0.238 322.16)",500:"oklch(66.7% 0.295 322.15)",600:"oklch(59.1% 0.293 322.896)",700:"oklch(51.8% 0.253 323.949)",800:"oklch(45.2% 0.211 324.591)",900:"oklch(40.1% 0.17 325.612)",950:"oklch(29.3% 0.136 325.661)"},pink:{50:"oklch(97.1% 0.014 343.198)",100:"oklch(94.8% 0.028 342.258)",200:"oklch(89.9% 0.061 343.231)",300:"oklch(82.3% 0.12 346.018)",400:"oklch(71.8% 0.202 349.761)",500:"oklch(65.6% 0.241 354.308)",600:"oklch(59.2% 0.249 0.584)",700:"oklch(52.5% 0.223 3.958)",800:"oklch(45.9% 0.187 3.815)",900:"oklch(40.8% 0.153 2.432)",950:"oklch(28.4% 0.109 3.907)"},rose:{50:"oklch(96.9% 0.015 12.422)",100:"oklch(94.1% 0.03 12.58)",200:"oklch(89.2% 0.058 10.001)",300:"oklch(81% 0.117 11.638)",400:"oklch(71.2% 0.194 13.428)",500:"oklch(64.5% 0.246 16.439)",600:"oklch(58.6% 0.253 17.585)",700:"oklch(51.4% 0.222 16.935)",800:"oklch(45.5% 0.188 13.697)",900:"oklch(41% 0.159 10.272)",950:"oklch(27.1% 0.105 12.094)"}};var zt=new Set(["black","silver","gray","white","maroon","red","purple","fuchsia","green","lime","olive","yellow","navy","blue","teal","aqua","aliceblue","antiquewhite","aqua","aquamarine","azure","beige","bisque","black","blanchedalmond","blue","blueviolet","brown","burlywood","cadetblue","chartreuse","chocolate","coral","cornflowerblue","cornsilk","crimson","cyan","darkblue","darkcyan","darkgoldenrod","darkgray","darkgreen","darkgrey","darkkhaki","darkmagenta","darkolivegreen","darkorange","darkorchid","darkred","darksalmon","darkseagreen","darkslateblue","darkslategray","darkslategrey","darkturquoise","darkviolet","deeppink","deepskyblue","dimgray","dimgrey","dodgerblue","firebrick","floralwhite","forestgreen","fuchsia","gainsboro","ghostwhite","gold","goldenrod","gray","green","greenyellow","grey","honeydew","hotpink","indianred","indigo","ivory","khaki","lavender","lavenderblush","lawngreen","lemonchiffon","lightblue","lightcoral","lightcyan","lightgoldenrodyellow","lightgray","lightgreen","lightgrey","lightpink","lightsalmon","lightseagreen","lightskyblue","lightslategray","lightslategrey","lightsteelblue","lightyellow","lime","limegreen","linen","magenta","maroon","mediumaquamarine","mediumblue","mediumorchid","mediumpurple","mediumseagreen","mediumslateblue","mediumspringgreen","mediumturquoise","mediumvioletred","midnightblue","mintcream","mistyrose","moccasin","navajowhite","navy","oldlace","olive","olivedrab","orange","orangered","orchid","palegoldenrod","palegreen","paleturquoise","palevioletred","papayawhip","peachpuff","peru","pink","plum","powderblue","purple","rebeccapurple","red","rosybrown","royalblue","saddlebrown","salmon","sandybrown","seagreen","seashell","sienna","silver","skyblue","slateblue","slategray","slategrey","snow","springgreen","steelblue","tan","teal","thistle","tomato","turquoise","violet","wheat","white","whitesmoke","yellow","yellowgreen","transparent","currentcolor","canvas","canvastext","linktext","visitedtext","activetext","buttonface","buttontext","buttonborder","field","fieldtext","highlight","highlighttext","selecteditem","selecteditemtext","mark","marktext","graytext","accentcolor","accentcolortext"]),Nt=/^(rgba?|hsla?|hwb|color|(ok)?(lab|lch)|light-dark|color-mix)\(/i;function Rt(A){return A.charCodeAt(0)===35||Nt.test(A)||zt.has(A.toLowerCase())}var hj=["calc","min","max","clamp","mod","rem","sin","cos","tan","asin","acos","atan","atan2","pow","sqrt","hypot","log","exp","round"];function CA(A){return A.indexOf("(")!==-1&&hj.some(e=>A.includes(`${e}(`))}function oe(A){if(!hj.some(n=>A.includes(n)))return A;let e="",t=[],j=null,r=null;for(let n=0;n<A.length;n++){let i=A.charCodeAt(n);if(i>=48&&i<=57||j!==null&&(i===37||i>=97&&i<=122||i>=65&&i<=90)?j=n:(r=j,j=null),i===40){e+=A[n];let l=n;for(let s=n-1;s>=0;s--){let h=A.charCodeAt(s);if(h>=48&&h<=57)l=s;else if(h>=97&&h<=122)l=s;else break}let c=A.slice(l,n);if(hj.includes(c)){t.unshift(!0);continue}else if(t[0]&&c===""){t.unshift(!0);continue}t.unshift(!1);continue}else if(i===41)e+=A[n],t.shift();else if(i===44&&t[0]){e+=", ";continue}else{if(i===32&&t[0]&&e.charCodeAt(e.length-1)===32)continue;if((i===43||i===42||i===47||i===45)&&t[0]){let l=e.trimEnd(),c=l.charCodeAt(l.length-1),s=l.charCodeAt(l.length-2),h=A.charCodeAt(n+1);if((c===101||c===69)&&s>=48&&s<=57){e+=A[n];continue}else if(c===43||c===42||c===47||c===45){e+=A[n];continue}else if(c===40||c===44){e+=A[n];continue}else A.charCodeAt(n-1)===32?e+=`${A[n]} `:c>=48&&c<=57||h>=48&&h<=57||c===41||h===40||h===43||h===42||h===47||h===45||r!==null&&r===n-1?e+=` ${A[n]} `:e+=A[n]}else e+=A[n]}}return e}var LA=new Uint8Array(256);function H(A,e){let t=0,j=[],r=0,n=A.length,i=e.charCodeAt(0);for(let l=0;l<n;l++){let c=A.charCodeAt(l);if(t===0&&c===i){j.push(A.slice(r,l)),r=l+1;continue}switch(c){case 92:l+=1;break;case 39:case 34:for(;++l<n;){let s=A.charCodeAt(l);if(s===92){l+=1;continue}if(s===c)break}break;case 40:LA[t]=41,t++;break;case 91:LA[t]=93,t++;break;case 123:LA[t]=125,t++;break;case 93:case 125:case 41:t>0&&c===LA[t-1]&&t--;break}}return j.push(A.slice(r)),j}var Xt={color:Rt,length:zA,percentage:uj,ratio:ar,number:ce,integer:F,url:ie,position:cr,"bg-size":nr,"line-width":Jt,image:Yt,"family-name":Qt,"generic-name":Zt,"absolute-size":Ar,"relative-size":jr,angle:dr,vector:hr};function V(A,e){if(A.startsWith("var("))return null;for(let t of e)if(Xt[t]?.(A))return t;return null}var Vt=/^url\(.*\)$/;function ie(A){return Vt.test(A)}function Jt(A){return H(A," ").every(e=>zA(e)||ce(e)||e==="thin"||e==="medium"||e==="thick")}var Ut=/^(?:element|image|cross-fade|image-set)\(/,Wt=/^(repeating-)?(conic|linear|radial)-gradient\(/;function Yt(A){let e=0;for(let t of H(A,","))if(!t.startsWith("var(")){if(ie(t)){e+=1;continue}if(Wt.test(t)){e+=1;continue}if(Ut.test(t)){e+=1;continue}return!1}return e>0}function Zt(A){return A==="serif"||A==="sans-serif"||A==="monospace"||A==="cursive"||A==="fantasy"||A==="system-ui"||A==="ui-serif"||A==="ui-sans-serif"||A==="ui-monospace"||A==="ui-rounded"||A==="math"||A==="emoji"||A==="fangsong"}function Qt(A){let e=0;for(let t of H(A,",")){let j=t.charCodeAt(0);if(j>=48&&j<=57)return!1;t.startsWith("var(")||(e+=1)}return e>0}function Ar(A){return A==="xx-small"||A==="x-small"||A==="small"||A==="medium"||A==="large"||A==="x-large"||A==="xx-large"||A==="xxx-large"}function jr(A){return A==="larger"||A==="smaller"}var lA=/[+-]?\d*\.?\d+(?:[eE][+-]?\d+)?/,er=new RegExp(`^${lA.source}$`);function ce(A){return er.test(A)||CA(A)}var tr=new RegExp(`^${lA.source}%$`);function uj(A){return tr.test(A)||CA(A)}var rr=new RegExp(`^${lA.source}s*/s*${lA.source}$`);function ar(A){return rr.test(A)||CA(A)}var or=["cm","mm","Q","in","pc","pt","px","em","ex","ch","rem","lh","rlh","vw","vh","vmin","vmax","vb","vi","svw","svh","lvw","lvh","dvw","dvh","cqw","cqh","cqi","cqb","cqmin","cqmax"],ir=new RegExp(`^${lA.source}(${or.join("|")})$`);function zA(A){return ir.test(A)||CA(A)}function cr(A){let e=0;for(let t of H(A," ")){if(t==="center"||t==="top"||t==="right"||t==="bottom"||t==="left"){e+=1;continue}if(!t.startsWith("var(")){if(zA(t)||uj(t)){e+=1;continue}return!1}}return e>0}function nr(A){let e=0;for(let t of H(A,",")){if(t==="cover"||t==="contain"){e+=1;continue}let j=H(t," ");if(j.length!==1&&j.length!==2)return!1;if(j.every(r=>r==="auto"||zA(r)||uj(r))){e+=1;continue}}return e>0}var sr=["deg","rad","grad","turn"],lr=new RegExp(`^${lA.source}(${sr.join("|")})$`);function dr(A){return lr.test(A)}var fr=new RegExp(`^${lA.source} +${lA.source} +${lA.source}$`);function hr(A){return fr.test(A)}function F(A){let e=Number(A);return Number.isInteger(e)&&e>=0&&String(e)===String(A)}function mj(A){let e=Number(A);return Number.isInteger(e)&&e>0&&String(e)===String(A)}function pA(A){return ne(A,.25)}function NA(A){return ne(A,.25)}function ne(A,e){let t=Number(A);return t>=0&&t%e===0&&String(t)===String(A)}function gA(A){return{__BARE_VALUE__:A}}var iA=gA(A=>{if(F(A.value))return A.value}),eA=gA(A=>{if(F(A.value))return`${A.value}%`}),hA=gA(A=>{if(F(A.value))return`${A.value}px`}),re=gA(A=>{if(F(A.value))return`${A.value}ms`}),SA=gA(A=>{if(F(A.value))return`${A.value}deg`}),ur=gA(A=>{if(A.fraction===null)return;let[e,t]=H(A.fraction,"/");if(!(!F(e)||!F(t)))return A.fraction}),ae=gA(A=>{if(F(Number(A.value)))return`repeat(${A.value}, minmax(0, 1fr))`}),se={accentColor:({theme:A})=>A("colors"),animation:{none:"none",spin:"spin 1s linear infinite",ping:"ping 1s cubic-bezier(0, 0, 0.2, 1) infinite",pulse:"pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite",bounce:"bounce 1s infinite"},aria:{busy:'busy="true"',checked:'checked="true"',disabled:'disabled="true"',expanded:'expanded="true"',hidden:'hidden="true"',pressed:'pressed="true"',readonly:'readonly="true"',required:'required="true"',selected:'selected="true"'},aspectRatio:{auto:"auto",square:"1 / 1",video:"16 / 9",...ur},backdropBlur:({theme:A})=>A("blur"),backdropBrightness:({theme:A})=>({...A("brightness"),...eA}),backdropContrast:({theme:A})=>({...A("contrast"),...eA}),backdropGrayscale:({theme:A})=>({...A("grayscale"),...eA}),backdropHueRotate:({theme:A})=>({...A("hueRotate"),...SA}),backdropInvert:({theme:A})=>({...A("invert"),...eA}),backdropOpacity:({theme:A})=>({...A("opacity"),...eA}),backdropSaturate:({theme:A})=>({...A("saturate"),...eA}),backdropSepia:({theme:A})=>({...A("sepia"),...eA}),backgroundColor:({theme:A})=>A("colors"),backgroundImage:{none:"none","gradient-to-t":"linear-gradient(to top, var(--tw-gradient-stops))","gradient-to-tr":"linear-gradient(to top right, var(--tw-gradient-stops))","gradient-to-r":"linear-gradient(to right, var(--tw-gradient-stops))","gradient-to-br":"linear-gradient(to bottom right, var(--tw-gradient-stops))","gradient-to-b":"linear-gradient(to bottom, var(--tw-gradient-stops))","gradient-to-bl":"linear-gradient(to bottom left, var(--tw-gradient-stops))","gradient-to-l":"linear-gradient(to left, var(--tw-gradient-stops))","gradient-to-tl":"linear-gradient(to top left, var(--tw-gradient-stops))"},backgroundOpacity:({theme:A})=>A("opacity"),backgroundPosition:{bottom:"bottom",center:"center",left:"left","left-bottom":"left bottom","left-top":"left top",right:"right","right-bottom":"right bottom","right-top":"right top",top:"top"},backgroundSize:{auto:"auto",cover:"cover",contain:"contain"},blur:{0:"0",none:"",sm:"4px",DEFAULT:"8px",md:"12px",lg:"16px",xl:"24px","2xl":"40px","3xl":"64px"},borderColor:({theme:A})=>({DEFAULT:"currentcolor",...A("colors")}),borderOpacity:({theme:A})=>A("opacity"),borderRadius:{none:"0px",sm:"0.125rem",DEFAULT:"0.25rem",md:"0.375rem",lg:"0.5rem",xl:"0.75rem","2xl":"1rem","3xl":"1.5rem",full:"9999px"},borderSpacing:({theme:A})=>A("spacing"),borderWidth:{DEFAULT:"1px",0:"0px",2:"2px",4:"4px",8:"8px",...hA},boxShadow:{sm:"0 1px 2px 0 rgb(0 0 0 / 0.05)",DEFAULT:"0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1)",md:"0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1)",lg:"0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1)",xl:"0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1)","2xl":"0 25px 50px -12px rgb(0 0 0 / 0.25)",inner:"inset 0 2px 4px 0 rgb(0 0 0 / 0.05)",none:"none"},boxShadowColor:({theme:A})=>A("colors"),brightness:{0:"0",50:".5",75:".75",90:".9",95:".95",100:"1",105:"1.05",110:"1.1",125:"1.25",150:"1.5",200:"2",...eA},caretColor:({theme:A})=>A("colors"),colors:()=>({...HA}),columns:{auto:"auto",1:"1",2:"2",3:"3",4:"4",5:"5",6:"6",7:"7",8:"8",9:"9",10:"10",11:"11",12:"12","3xs":"16rem","2xs":"18rem",xs:"20rem",sm:"24rem",md:"28rem",lg:"32rem",xl:"36rem","2xl":"42rem","3xl":"48rem","4xl":"56rem","5xl":"64rem","6xl":"72rem","7xl":"80rem",...iA},container:{},content:{none:"none"},contrast:{0:"0",50:".5",75:".75",100:"1",125:"1.25",150:"1.5",200:"2",...eA},cursor:{auto:"auto",default:"default",pointer:"pointer",wait:"wait",text:"text",move:"move",help:"help","not-allowed":"not-allowed",none:"none","context-menu":"context-menu",progress:"progress",cell:"cell",crosshair:"crosshair","vertical-text":"vertical-text",alias:"alias",copy:"copy","no-drop":"no-drop",grab:"grab",grabbing:"grabbing","all-scroll":"all-scroll","col-resize":"col-resize","row-resize":"row-resize","n-resize":"n-resize","e-resize":"e-resize","s-resize":"s-resize","w-resize":"w-resize","ne-resize":"ne-resize","nw-resize":"nw-resize","se-resize":"se-resize","sw-resize":"sw-resize","ew-resize":"ew-resize","ns-resize":"ns-resize","nesw-resize":"nesw-resize","nwse-resize":"nwse-resize","zoom-in":"zoom-in","zoom-out":"zoom-out"},divideColor:({theme:A})=>A("borderColor"),divideOpacity:({theme:A})=>A("borderOpacity"),divideWidth:({theme:A})=>({...A("borderWidth"),...hA}),dropShadow:{sm:"0 1px 1px rgb(0 0 0 / 0.05)",DEFAULT:["0 1px 2px rgb(0 0 0 / 0.1)","0 1px 1px rgb(0 0 0 / 0.06)"],md:["0 4px 3px rgb(0 0 0 / 0.07)","0 2px 2px rgb(0 0 0 / 0.06)"],lg:["0 10px 8px rgb(0 0 0 / 0.04)","0 4px 3px rgb(0 0 0 / 0.1)"],xl:["0 20px 13px rgb(0 0 0 / 0.03)","0 8px 5px rgb(0 0 0 / 0.08)"],"2xl":"0 25px 25px rgb(0 0 0 / 0.15)",none:"0 0 #0000"},fill:({theme:A})=>A("colors"),flex:{1:"1 1 0%",auto:"1 1 auto",initial:"0 1 auto",none:"none"},flexBasis:({theme:A})=>({auto:"auto","1/2":"50%","1/3":"33.333333%","2/3":"66.666667%","1/4":"25%","2/4":"50%","3/4":"75%","1/5":"20%","2/5":"40%","3/5":"60%","4/5":"80%","1/6":"16.666667%","2/6":"33.333333%","3/6":"50%","4/6":"66.666667%","5/6":"83.333333%","1/12":"8.333333%","2/12":"16.666667%","3/12":"25%","4/12":"33.333333%","5/12":"41.666667%","6/12":"50%","7/12":"58.333333%","8/12":"66.666667%","9/12":"75%","10/12":"83.333333%","11/12":"91.666667%",full:"100%",...A("spacing")}),flexGrow:{0:"0",DEFAULT:"1",...iA},flexShrink:{0:"0",DEFAULT:"1",...iA},fontFamily:{sans:["ui-sans-serif","system-ui","sans-serif",'"Apple Color Emoji"','"Segoe UI Emoji"','"Segoe UI Symbol"','"Noto Color Emoji"'],serif:["ui-serif","Georgia","Cambria",'"Times New Roman"',"Times","serif"],mono:["ui-monospace","SFMono-Regular","Menlo","Monaco","Consolas",'"Liberation Mono"','"Courier New"',"monospace"]},fontSize:{xs:["0.75rem",{lineHeight:"1rem"}],sm:["0.875rem",{lineHeight:"1.25rem"}],base:["1rem",{lineHeight:"1.5rem"}],lg:["1.125rem",{lineHeight:"1.75rem"}],xl:["1.25rem",{lineHeight:"1.75rem"}],"2xl":["1.5rem",{lineHeight:"2rem"}],"3xl":["1.875rem",{lineHeight:"2.25rem"}],"4xl":["2.25rem",{lineHeight:"2.5rem"}],"5xl":["3rem",{lineHeight:"1"}],"6xl":["3.75rem",{lineHeight:"1"}],"7xl":["4.5rem",{lineHeight:"1"}],"8xl":["6rem",{lineHeight:"1"}],"9xl":["8rem",{lineHeight:"1"}]},fontWeight:{thin:"100",extralight:"200",light:"300",normal:"400",medium:"500",semibold:"600",bold:"700",extrabold:"800",black:"900"},gap:({theme:A})=>A("spacing"),gradientColorStops:({theme:A})=>A("colors"),gradientColorStopPositions:{"0%":"0%","5%":"5%","10%":"10%","15%":"15%","20%":"20%","25%":"25%","30%":"30%","35%":"35%","40%":"40%","45%":"45%","50%":"50%","55%":"55%","60%":"60%","65%":"65%","70%":"70%","75%":"75%","80%":"80%","85%":"85%","90%":"90%","95%":"95%","100%":"100%",...eA},grayscale:{0:"0",DEFAULT:"100%",...eA},gridAutoColumns:{auto:"auto",min:"min-content",max:"max-content",fr:"minmax(0, 1fr)"},gridAutoRows:{auto:"auto",min:"min-content",max:"max-content",fr:"minmax(0, 1fr)"},gridColumn:{auto:"auto","span-1":"span 1 / span 1","span-2":"span 2 / span 2","span-3":"span 3 / span 3","span-4":"span 4 / span 4","span-5":"span 5 / span 5","span-6":"span 6 / span 6","span-7":"span 7 / span 7","span-8":"span 8 / span 8","span-9":"span 9 / span 9","span-10":"span 10 / span 10","span-11":"span 11 / span 11","span-12":"span 12 / span 12","span-full":"1 / -1"},gridColumnEnd:{auto:"auto",1:"1",2:"2",3:"3",4:"4",5:"5",6:"6",7:"7",8:"8",9:"9",10:"10",11:"11",12:"12",13:"13",...iA},gridColumnStart:{auto:"auto",1:"1",2:"2",3:"3",4:"4",5:"5",6:"6",7:"7",8:"8",9:"9",10:"10",11:"11",12:"12",13:"13",...iA},gridRow:{auto:"auto","span-1":"span 1 / span 1","span-2":"span 2 / span 2","span-3":"span 3 / span 3","span-4":"span 4 / span 4","span-5":"span 5 / span 5","span-6":"span 6 / span 6","span-7":"span 7 / span 7","span-8":"span 8 / span 8","span-9":"span 9 / span 9","span-10":"span 10 / span 10","span-11":"span 11 / span 11","span-12":"span 12 / span 12","span-full":"1 / -1"},gridRowEnd:{auto:"auto",1:"1",2:"2",3:"3",4:"4",5:"5",6:"6",7:"7",8:"8",9:"9",10:"10",11:"11",12:"12",13:"13",...iA},gridRowStart:{auto:"auto",1:"1",2:"2",3:"3",4:"4",5:"5",6:"6",7:"7",8:"8",9:"9",10:"10",11:"11",12:"12",13:"13",...iA},gridTemplateColumns:{none:"none",subgrid:"subgrid",1:"repeat(1, minmax(0, 1fr))",2:"repeat(2, minmax(0, 1fr))",3:"repeat(3, minmax(0, 1fr))",4:"repeat(4, minmax(0, 1fr))",5:"repeat(5, minmax(0, 1fr))",6:"repeat(6, minmax(0, 1fr))",7:"repeat(7, minmax(0, 1fr))",8:"repeat(8, minmax(0, 1fr))",9:"repeat(9, minmax(0, 1fr))",10:"repeat(10, minmax(0, 1fr))",11:"repeat(11, minmax(0, 1fr))",12:"repeat(12, minmax(0, 1fr))",...ae},gridTemplateRows:{none:"none",subgrid:"subgrid",1:"repeat(1, minmax(0, 1fr))",2:"repeat(2, minmax(0, 1fr))",3:"repeat(3, minmax(0, 1fr))",4:"repeat(4, minmax(0, 1fr))",5:"repeat(5, minmax(0, 1fr))",6:"repeat(6, minmax(0, 1fr))",7:"repeat(7, minmax(0, 1fr))",8:"repeat(8, minmax(0, 1fr))",9:"repeat(9, minmax(0, 1fr))",10:"repeat(10, minmax(0, 1fr))",11:"repeat(11, minmax(0, 1fr))",12:"repeat(12, minmax(0, 1fr))",...ae},height:({theme:A})=>({auto:"auto","1/2":"50%","1/3":"33.333333%","2/3":"66.666667%","1/4":"25%","2/4":"50%","3/4":"75%","1/5":"20%","2/5":"40%","3/5":"60%","4/5":"80%","1/6":"16.666667%","2/6":"33.333333%","3/6":"50%","4/6":"66.666667%","5/6":"83.333333%",full:"100%",screen:"100vh",svh:"100svh",lvh:"100lvh",dvh:"100dvh",min:"min-content",max:"max-content",fit:"fit-content",...A("spacing")}),hueRotate:{0:"0deg",15:"15deg",30:"30deg",60:"60deg",90:"90deg",180:"180deg",...SA},inset:({theme:A})=>({auto:"auto","1/2":"50%","1/3":"33.333333%","2/3":"66.666667%","1/4":"25%","2/4":"50%","3/4":"75%",full:"100%",...A("spacing")}),invert:{0:"0",DEFAULT:"100%",...eA},keyframes:{spin:{to:{transform:"rotate(360deg)"}},ping:{"75%, 100%":{transform:"scale(2)",opacity:"0"}},pulse:{"50%":{opacity:".5"}},bounce:{"0%, 100%":{transform:"translateY(-25%)",animationTimingFunction:"cubic-bezier(0.8,0,1,1)"},"50%":{transform:"none",animationTimingFunction:"cubic-bezier(0,0,0.2,1)"}}},letterSpacing:{tighter:"-0.05em",tight:"-0.025em",normal:"0em",wide:"0.025em",wider:"0.05em",widest:"0.1em"},lineHeight:{none:"1",tight:"1.25",snug:"1.375",normal:"1.5",relaxed:"1.625",loose:"2",3:".75rem",4:"1rem",5:"1.25rem",6:"1.5rem",7:"1.75rem",8:"2rem",9:"2.25rem",10:"2.5rem"},listStyleType:{none:"none",disc:"disc",decimal:"decimal"},listStyleImage:{none:"none"},margin:({theme:A})=>({auto:"auto",...A("spacing")}),lineClamp:{1:"1",2:"2",3:"3",4:"4",5:"5",6:"6",...iA},maxHeight:({theme:A})=>({none:"none",full:"100%",screen:"100vh",svh:"100svh",lvh:"100lvh",dvh:"100dvh",min:"min-content",max:"max-content",fit:"fit-content",...A("spacing")}),maxWidth:({theme:A})=>({none:"none",xs:"20rem",sm:"24rem",md:"28rem",lg:"32rem",xl:"36rem","2xl":"42rem","3xl":"48rem","4xl":"56rem","5xl":"64rem","6xl":"72rem","7xl":"80rem",full:"100%",min:"min-content",max:"max-content",fit:"fit-content",prose:"65ch",...A("spacing")}),minHeight:({theme:A})=>({full:"100%",screen:"100vh",svh:"100svh",lvh:"100lvh",dvh:"100dvh",min:"min-content",max:"max-content",fit:"fit-content",...A("spacing")}),minWidth:({theme:A})=>({full:"100%",min:"min-content",max:"max-content",fit:"fit-content",...A("spacing")}),objectPosition:{bottom:"bottom",center:"center",left:"left","left-bottom":"left bottom","left-top":"left top",right:"right","right-bottom":"right bottom","right-top":"right top",top:"top"},opacity:{0:"0",5:"0.05",10:"0.1",15:"0.15",20:"0.2",25:"0.25",30:"0.3",35:"0.35",40:"0.4",45:"0.45",50:"0.5",55:"0.55",60:"0.6",65:"0.65",70:"0.7",75:"0.75",80:"0.8",85:"0.85",90:"0.9",95:"0.95",100:"1",...eA},order:{first:"-9999",last:"9999",none:"0",1:"1",2:"2",3:"3",4:"4",5:"5",6:"6",7:"7",8:"8",9:"9",10:"10",11:"11",12:"12",...iA},outlineColor:({theme:A})=>A("colors"),outlineOffset:{0:"0px",1:"1px",2:"2px",4:"4px",8:"8px",...hA},outlineWidth:{0:"0px",1:"1px",2:"2px",4:"4px",8:"8px",...hA},padding:({theme:A})=>A("spacing"),placeholderColor:({theme:A})=>A("colors"),placeholderOpacity:({theme:A})=>A("opacity"),ringColor:({theme:A})=>({DEFAULT:"currentcolor",...A("colors")}),ringOffsetColor:({theme:A})=>A("colors"),ringOffsetWidth:{0:"0px",1:"1px",2:"2px",4:"4px",8:"8px",...hA},ringOpacity:({theme:A})=>({DEFAULT:"0.5",...A("opacity")}),ringWidth:{DEFAULT:"3px",0:"0px",1:"1px",2:"2px",4:"4px",8:"8px",...hA},rotate:{0:"0deg",1:"1deg",2:"2deg",3:"3deg",6:"6deg",12:"12deg",45:"45deg",90:"90deg",180:"180deg",...SA},saturate:{0:"0",50:".5",100:"1",150:"1.5",200:"2",...eA},scale:{0:"0",50:".5",75:".75",90:".9",95:".95",100:"1",105:"1.05",110:"1.1",125:"1.25",150:"1.5",...eA},screens:{sm:"40rem",md:"48rem",lg:"64rem",xl:"80rem","2xl":"96rem"},scrollMargin:({theme:A})=>A("spacing"),scrollPadding:({theme:A})=>A("spacing"),sepia:{0:"0",DEFAULT:"100%",...eA},skew:{0:"0deg",1:"1deg",2:"2deg",3:"3deg",6:"6deg",12:"12deg",...SA},space:({theme:A})=>A("spacing"),spacing:{px:"1px",0:"0px",.5:"0.125rem",1:"0.25rem",1.5:"0.375rem",2:"0.5rem",2.5:"0.625rem",3:"0.75rem",3.5:"0.875rem",4:"1rem",5:"1.25rem",6:"1.5rem",7:"1.75rem",8:"2rem",9:"2.25rem",10:"2.5rem",11:"2.75rem",12:"3rem",14:"3.5rem",16:"4rem",20:"5rem",24:"6rem",28:"7rem",32:"8rem",36:"9rem",40:"10rem",44:"11rem",48:"12rem",52:"13rem",56:"14rem",60:"15rem",64:"16rem",72:"18rem",80:"20rem",96:"24rem"},stroke:({theme:A})=>({none:"none",...A("colors")}),strokeWidth:{0:"0",1:"1",2:"2",...iA},supports:{},data:{},textColor:({theme:A})=>A("colors"),textDecorationColor:({theme:A})=>A("colors"),textDecorationThickness:{auto:"auto","from-font":"from-font",0:"0px",1:"1px",2:"2px",4:"4px",8:"8px",...hA},textIndent:({theme:A})=>A("spacing"),textOpacity:({theme:A})=>A("opacity"),textUnderlineOffset:{auto:"auto",0:"0px",1:"1px",2:"2px",4:"4px",8:"8px",...hA},transformOrigin:{center:"center",top:"top","top-right":"top right",right:"right","bottom-right":"bottom right",bottom:"bottom","bottom-left":"bottom left",left:"left","top-left":"top left"},transitionDelay:{0:"0s",75:"75ms",100:"100ms",150:"150ms",200:"200ms",300:"300ms",500:"500ms",700:"700ms",1e3:"1000ms",...re},transitionDuration:{DEFAULT:"150ms",0:"0s",75:"75ms",100:"100ms",150:"150ms",200:"200ms",300:"300ms",500:"500ms",700:"700ms",1e3:"1000ms",...re},transitionProperty:{none:"none",all:"all",DEFAULT:"color, background-color, border-color, outline-color, text-decoration-color, fill, stroke, opacity, box-shadow, transform, filter, backdrop-filter",colors:"color, background-color, border-color, outline-color, text-decoration-color, fill, stroke",opacity:"opacity",shadow:"box-shadow",transform:"transform"},transitionTimingFunction:{DEFAULT:"cubic-bezier(0.4, 0, 0.2, 1)",linear:"linear",in:"cubic-bezier(0.4, 0, 1, 1)",out:"cubic-bezier(0, 0, 0.2, 1)","in-out":"cubic-bezier(0.4, 0, 0.2, 1)"},translate:({theme:A})=>({"1/2":"50%","1/3":"33.333333%","2/3":"66.666667%","1/4":"25%","2/4":"50%","3/4":"75%",full:"100%",...A("spacing")}),size:({theme:A})=>({auto:"auto","1/2":"50%","1/3":"33.333333%","2/3":"66.666667%","1/4":"25%","2/4":"50%","3/4":"75%","1/5":"20%","2/5":"40%","3/5":"60%","4/5":"80%","1/6":"16.666667%","2/6":"33.333333%","3/6":"50%","4/6":"66.666667%","5/6":"83.333333%","1/12":"8.333333%","2/12":"16.666667%","3/12":"25%","4/12":"33.333333%","5/12":"41.666667%","6/12":"50%","7/12":"58.333333%","8/12":"66.666667%","9/12":"75%","10/12":"83.333333%","11/12":"91.666667%",full:"100%",min:"min-content",max:"max-content",fit:"fit-content",...A("spacing")}),width:({theme:A})=>({auto:"auto","1/2":"50%","1/3":"33.333333%","2/3":"66.666667%","1/4":"25%","2/4":"50%","3/4":"75%","1/5":"20%","2/5":"40%","3/5":"60%","4/5":"80%","1/6":"16.666667%","2/6":"33.333333%","3/6":"50%","4/6":"66.666667%","5/6":"83.333333%","1/12":"8.333333%","2/12":"16.666667%","3/12":"25%","4/12":"33.333333%","5/12":"41.666667%","6/12":"50%","7/12":"58.333333%","8/12":"66.666667%","9/12":"75%","10/12":"83.333333%","11/12":"91.666667%",full:"100%",screen:"100vw",svw:"100svw",lvw:"100lvw",dvw:"100dvw",min:"min-content",max:"max-content",fit:"fit-content",...A("spacing")}),willChange:{auto:"auto",scroll:"scroll-position",contents:"contents",transform:"transform"},zIndex:{auto:"auto",0:"0",10:"10",20:"20",30:"30",40:"40",50:"50",...iA}};var mr="4.1.12",FA=92,RA=47,XA=42,le=34,de=39,pr=58,UA=59,cA=10,QA=13,wA=32,VA=9,fe=123,pj=125,Bj=40,he=41,gr=91,kr=93,ue=45,gj=64,br=33;function Pj(A,e){let t=e?.from?{file:e.from,code:A}:null;A[0]==="\uFEFF"&&(A=" "+A.slice(1));let j=[],r=[],n=[],i=null,l=null,c="",s="",h=0,f;for(let u=0;u<A.length;u++){let g=A.charCodeAt(u);if(!(g===QA&&(f=A.charCodeAt(u+1),f===cA)))if(g===FA)c===""&&(h=u),c+=A.slice(u,u+2),u+=1;else if(g===RA&&A.charCodeAt(u+1)===XA){let p=u;for(let x=u+2;x<A.length;x++)if(f=A.charCodeAt(x),f===FA)x+=1;else if(f===XA&&A.charCodeAt(x+1)===RA){u=x+1;break}let E=A.slice(p,u+1);if(E.charCodeAt(2)===br){let x=ct(E.slice(2,-2));r.push(x),t&&(x.src=[t,p,u+1],x.dst=[t,p,u+1])}}else if(g===de||g===le){let p=me(A,u,g);c+=A.slice(u,p+1),u=p}else{if((g===wA||g===cA||g===VA)&&(f=A.charCodeAt(u+1))&&(f===wA||f===cA||f===VA||f===QA&&(f=A.charCodeAt(u+2))&&f==cA))continue;if(g===cA){if(c.length===0)continue;f=c.charCodeAt(c.length-1),f!==wA&&f!==cA&&f!==VA&&(c+=" ")}else if(g===ue&&A.charCodeAt(u+1)===ue&&c.length===0){let p="",E=u,x=-1;for(let b=u+2;b<A.length;b++)if(f=A.charCodeAt(b),f===FA)b+=1;else if(f===de||f===le)b=me(A,b,f);else if(f===RA&&A.charCodeAt(b+1)===XA){for(let G=b+2;G<A.length;G++)if(f=A.charCodeAt(G),f===FA)G+=1;else if(f===XA&&A.charCodeAt(G+1)===RA){b=G+1;break}}else if(x===-1&&f===pr)x=c.length+b-E;else if(f===UA&&p.length===0){c+=A.slice(E,b),u=b;break}else if(f===Bj)p+=")";else if(f===gr)p+="]";else if(f===fe)p+="}";else if((f===pj||A.length-1===b)&&p.length===0){u=b-1,c+=A.slice(E,b);break}else(f===he||f===kr||f===pj)&&p.length>0&&A[b]===p[p.length-1]&&(p=p.slice(0,-1));let I=kj(c,x);if(!I)throw new Error("Invalid custom property, expected a value");t&&(I.src=[t,E,u],I.dst=[t,E,u]),i?i.nodes.push(I):j.push(I),c=""}else if(g===UA&&c.charCodeAt(0)===gj)l=WA(c),t&&(l.src=[t,h,u],l.dst=[t,h,u]),i?i.nodes.push(l):j.push(l),c="",l=null;else if(g===UA&&s[s.length-1]!==")"){let p=kj(c);if(!p){if(c.length===0)continue;throw new Error(`Invalid declaration: \`${c.trim()}\``)}t&&(p.src=[t,h,u],p.dst=[t,h,u]),i?i.nodes.push(p):j.push(p),c=""}else if(g===fe&&s[s.length-1]!==")")s+="}",l=Z(c.trim()),t&&(l.src=[t,h,u],l.dst=[t,h,u]),i&&i.nodes.push(l),n.push(i),i=l,c="",l=null;else if(g===pj&&s[s.length-1]!==")"){if(s==="")throw new Error("Missing opening {");if(s=s.slice(0,-1),c.length>0)if(c.charCodeAt(0)===gj)l=WA(c),t&&(l.src=[t,h,u],l.dst=[t,h,u]),i?i.nodes.push(l):j.push(l),c="",l=null;else{let E=c.indexOf(":");if(i){let x=kj(c,E);if(!x)throw new Error(`Invalid declaration: \`${c.trim()}\``);t&&(x.src=[t,h,u],x.dst=[t,h,u]),i.nodes.push(x)}}let p=n.pop()??null;p===null&&i&&j.push(i),i=p,c="",l=null}else if(g===Bj)s+=")",c+="(";else if(g===he){if(s[s.length-1]!==")")throw new Error("Missing opening (");s=s.slice(0,-1),c+=")"}else{if(c.length===0&&(g===wA||g===cA||g===VA))continue;c===""&&(h=u),c+=String.fromCharCode(g)}}}if(c.charCodeAt(0)===gj){let u=WA(c);t&&(u.src=[t,h,A.length],u.dst=[t,h,A.length]),j.push(u)}if(s.length>0&&i){if(i.kind==="rule")throw new Error(`Missing closing } at ${i.selector}`);if(i.kind==="at-rule")throw new Error(`Missing closing } at ${i.name} ${i.params}`)}return r.length>0?r.concat(j):j}function WA(A,e=[]){let t=A,j="";for(let r=5;r<A.length;r++){let n=A.charCodeAt(r);if(n===wA||n===Bj){t=A.slice(0,r),j=A.slice(r);break}}return R(t.trim(),j.trim(),e)}function kj(A,e=A.indexOf(":")){if(e===-1)return null;let t=A.indexOf("!important",e+1);return o(A.slice(0,e).trim(),A.slice(e+1,t===-1?A.length:t).trim(),t!==-1)}function me(A,e,t){let j;for(let r=e+1;r<A.length;r++)if(j=A.charCodeAt(r),j===FA)r+=1;else{if(j===t)return r;if(j===UA&&(A.charCodeAt(r+1)===cA||A.charCodeAt(r+1)===QA&&A.charCodeAt(r+2)===cA))throw new Error(`Unterminated string: ${A.slice(e,r+1)+String.fromCharCode(t)}`);if(j===cA||j===QA&&A.charCodeAt(r+1)===cA)throw new Error(`Unterminated string: ${A.slice(e,r)+String.fromCharCode(t)}`)}return e}function oj(A){if(arguments.length===0)throw new TypeError("`CSS.escape` requires an argument.");let e=String(A),t=e.length,j=-1,r,n="",i=e.charCodeAt(0);if(t===1&&i===45)return"\\"+e;for(;++j<t;){if(r=e.charCodeAt(j),r===0){n+="\uFFFD";continue}if(r>=1&&r<=31||r===127||j===0&&r>=48&&r<=57||j===1&&r>=48&&r<=57&&i===45){n+="\\"+r.toString(16)+" ";continue}if(r>=128||r===45||r===95||r>=48&&r<=57||r>=65&&r<=90||r>=97&&r<=122){n+=e.charAt(j);continue}n+="\\"+e.charAt(j)}return n}function Aj(A){return A.replace(/\\([\dA-Fa-f]{1,6}[\t\n\f\r ]?|[\S\s])/g,e=>e.length>2?String.fromCodePoint(Number.parseInt(e.slice(1).trim(),16)):e[1])}var ot=new Map([["--font",["--font-weight","--font-size"]],["--inset",["--inset-shadow","--inset-ring"]],["--text",["--text-color","--text-decoration-color","--text-decoration-thickness","--text-indent","--text-shadow","--text-underline-offset"]]]);function pe(A,e){return(ot.get(e)??[]).some(t=>A===t||A.startsWith(`${t}-`))}var aA,xj,YA,ZA,at,Er=(at=class{constructor(A=new Map,e=new Set([])){zj(this,aA);nA(this,"prefix",null);this.values=A,this.keyframes=e}get size(){return this.values.size}add(A,e,t=0,j){if(A.endsWith("-*")){if(e!=="initial")throw new Error(`Invalid theme value \`${e}\` for namespace \`${A}\``);A==="--*"?this.values.clear():this.clearNamespace(A.slice(0,-2),0)}if(t&4){let r=this.values.get(A);if(r&&!(r.options&4))return}e==="initial"?this.values.delete(A):this.values.set(A,{value:e,options:t,src:j})}keysInNamespaces(A){let e=[];for(let t of A){let j=`${t}-`;for(let r of this.values.keys())r.startsWith(j)&&r.indexOf("--",2)===-1&&(pe(r,t)||e.push(r.slice(j.length)))}return e}get(A){for(let e of A){let t=this.values.get(e);if(t)return t.value}return null}hasDefault(A){return(this.getOptions(A)&4)===4}getOptions(A){return A=Aj(sA(this,aA,xj).call(this,A)),this.values.get(A)?.options??0}entries(){return this.prefix?Array.from(this.values,A=>(A[0]=this.prefixKey(A[0]),A)):this.values.entries()}prefixKey(A){return this.prefix?`--${this.prefix}-${A.slice(2)}`:A}clearNamespace(A,e){let t=ot.get(A)??[];A:for(let j of this.values.keys())if(j.startsWith(A)){if(e!==0&&(this.getOptions(j)&e)!==e)continue;for(let r of t)if(j.startsWith(r))continue A;this.values.delete(j)}}markUsedVariable(A){let e=Aj(sA(this,aA,xj).call(this,A)),t=this.values.get(e);if(!t)return!1;let j=t.options&16;return t.options|=16,!j}resolve(A,e,t=0){let j=sA(this,aA,YA).call(this,A,e);if(!j)return null;let r=this.values.get(j);return(t|r.options)&1?r.value:sA(this,aA,ZA).call(this,j)}resolveValue(A,e){let t=sA(this,aA,YA).call(this,A,e);return t?this.values.get(t).value:null}resolveWith(A,e,t=[]){let j=sA(this,aA,YA).call(this,A,e);if(!j)return null;let r={};for(let i of t){let l=`${j}${i}`,c=this.values.get(l);c&&(c.options&1?r[i]=c.value:r[i]=sA(this,aA,ZA).call(this,l))}let n=this.values.get(j);return n.options&1?[n.value,r]:[sA(this,aA,ZA).call(this,j),r]}namespace(A){let e=new Map,t=`${A}-`;for(let[j,r]of this.values)j===A?e.set(null,r.value):j.startsWith(`${t}-`)?e.set(j.slice(A.length),r.value):j.startsWith(t)&&e.set(j.slice(t.length),r.value);return e}addKeyframes(A){this.keyframes.add(A)}getKeyframes(){return Array.from(this.keyframes)}},aA=new WeakSet,xj=function(A){return this.prefix?`--${A.slice(3+this.prefix.length)}`:A},YA=function(A,e){for(let t of e){let j=A!==null?`${t}-${A}`:t;if(!this.values.has(j))if(A!==null&&A.includes(".")){if(j=`${t}-${A.replaceAll(".","_")}`,!this.values.has(j))continue}else continue;if(!pe(j,t))return j}return null},ZA=function(A){let e=this.values.get(A);if(!e)return null;let t=null;return e.options&2&&(t=e.value),`var(${oj(this.prefixKey(A))}${t?`, ${t}`:""})`},at),U=class extends Map{constructor(A){super(),this.factory=A}get(A){let e=super.get(A);return e===void 0&&(e=this.factory(A,this),this.set(A,e)),e}};function bj(A){return{kind:"word",value:A}}function Br(A,e){return{kind:"function",value:A,nodes:e}}function xr(A){return{kind:"separator",value:A}}function rA(A,e,t=null){for(let j=0;j<A.length;j++){let r=A[j],n=!1,i=0,l=e(r,{parent:t,replaceWith(c){n||(n=!0,Array.isArray(c)?c.length===0?(A.splice(j,1),i=0):c.length===1?(A[j]=c[0],i=1):(A.splice(j,1,...c),i=c.length):A[j]=c)}})??0;if(n){l===0?j--:j+=i-1;continue}if(l===2||l!==1&&r.kind==="function"&&rA(r.nodes,e,r)===2)return 2}}function jA(A){let e="";for(let t of A)switch(t.kind){case"word":case"separator":{e+=t.value;break}case"function":e+=t.value+"("+jA(t.nodes)+")"}return e}var ge=92,_r=41,ke=58,be=44,vr=34,Ee=61,Be=62,xe=60,_e=10,$r=40,Ir=39,ve=47,$e=32,Ie=9;function W(A){A=A.replaceAll(`\r
`,`
`);let e=[],t=[],j=null,r="",n;for(let i=0;i<A.length;i++){let l=A.charCodeAt(i);switch(l){case ge:{r+=A[i]+A[i+1],i++;break}case ke:case be:case Ee:case Be:case xe:case _e:case ve:case $e:case Ie:{if(r.length>0){let f=bj(r);j?j.nodes.push(f):e.push(f),r=""}let c=i,s=i+1;for(;s<A.length&&(n=A.charCodeAt(s),!(n!==ke&&n!==be&&n!==Ee&&n!==Be&&n!==xe&&n!==_e&&n!==ve&&n!==$e&&n!==Ie));s++);i=s-1;let h=xr(A.slice(c,s));j?j.nodes.push(h):e.push(h);break}case Ir:case vr:{let c=i;for(let s=i+1;s<A.length;s++)if(n=A.charCodeAt(s),n===ge)s+=1;else if(n===l){i=s;break}r+=A.slice(c,i+1);break}case $r:{let c=Br(r,[]);r="",j?j.nodes.push(c):e.push(c),t.push(c),j=c;break}case _r:{let c=t.pop();if(r.length>0){let s=bj(r);c?.nodes.push(s),r=""}t.length>0?j=t[t.length-1]:j=null;break}default:r+=String.fromCharCode(l)}}return r.length>0&&e.push(bj(r)),e}function it(A){let e=[];return rA(W(A),t=>{if(!(t.kind!=="function"||t.value!=="var"))return rA(t.nodes,j=>{j.kind!=="word"||j.value[0]!=="-"||j.value[1]!=="-"||e.push(j.value)}),1}),e}var qr=64;function X(A,e=[]){return{kind:"rule",selector:A,nodes:e}}function R(A,e="",t=[]){return{kind:"at-rule",name:A,params:e,nodes:t}}function Z(A,e=[]){return A.charCodeAt(0)===qr?WA(A,e):X(A,e)}function o(A,e,t=!1){return{kind:"declaration",property:A,value:e,important:t}}function ct(A){return{kind:"comment",value:A}}function kA(A,e){return{kind:"context",context:A,nodes:e}}function S(A){return{kind:"at-root",nodes:A}}function z(A,e,t=[],j={}){for(let r=0;r<A.length;r++){let n=A[r],i=t[t.length-1]??null;if(n.kind==="context"){if(z(n.nodes,e,t,{...j,...n.context})===2)return 2;continue}t.push(n);let l=!1,c=0,s=e(n,{parent:i,context:j,path:t,replaceWith(h){l||(l=!0,Array.isArray(h)?h.length===0?(A.splice(r,1),c=0):h.length===1?(A[r]=h[0],c=1):(A.splice(r,1,...h),c=h.length):(A[r]=h,c=1))}})??0;if(t.pop(),l){s===0?r--:r+=c-1;continue}if(s===2)return 2;if(s!==1&&"nodes"in n){t.push(n);let h=z(n.nodes,e,t,j);if(t.pop(),h===2)return 2}}}function _j(A,e,t=[],j={}){for(let r=0;r<A.length;r++){let n=A[r],i=t[t.length-1]??null;if(n.kind==="rule"||n.kind==="at-rule")t.push(n),_j(n.nodes,e,t,j),t.pop();else if(n.kind==="context"){_j(n.nodes,e,t,{...j,...n.context});continue}t.push(n),e(n,{parent:i,context:j,path:t,replaceWith(l){Array.isArray(l)?l.length===0?A.splice(r,1):l.length===1?A[r]=l[0]:A.splice(r,1,...l):A[r]=l,r+=l.length-1}}),t.pop()}}function yA(A,e,t=3){let j=[],r=new Set,n=new U(()=>new Set),i=new U(()=>new Set),l=new Set,c=new Set,s=[],h=[],f=new U(()=>new Set);function u(p,E,x={},I=0){if(p.kind==="declaration"){if(p.property==="--tw-sort"||p.value===void 0||p.value===null)return;if(x.theme&&p.property[0]==="-"&&p.property[1]==="-"){if(p.value==="initial"){p.value=void 0;return}x.keyframes||n.get(E).add(p)}if(p.value.includes("var("))if(x.theme&&p.property[0]==="-"&&p.property[1]==="-")for(let b of it(p.value))f.get(b).add(p.property);else e.trackUsedVariables(p.value);if(p.property==="animation")for(let b of qe(p.value))c.add(b);t&2&&p.value.includes("color-mix(")&&i.get(E).add(p),E.push(p)}else if(p.kind==="rule")if(p.selector==="&")for(let b of p.nodes){let G=[];u(b,G,x,I+1),G.length>0&&E.push(...G)}else{let b={...p,nodes:[]};for(let G of p.nodes)u(G,b.nodes,x,I+1);b.nodes.length>0&&E.push(b)}else if(p.kind==="at-rule"&&p.name==="@property"&&I===0){if(r.has(p.params))return;if(t&1){let G=p.params,w=null,P=!1;for(let D of p.nodes)D.kind==="declaration"&&(D.property==="initial-value"?w=D.value:D.property==="inherits"&&(P=D.value==="true"));let K=o(G,w??"initial");K.src=p.src,P?s.push(K):h.push(K)}r.add(p.params);let b={...p,nodes:[]};for(let G of p.nodes)u(G,b.nodes,x,I+1);E.push(b)}else if(p.kind==="at-rule"){p.name==="@keyframes"&&(x={...x,keyframes:!0});let b={...p,nodes:[]};for(let G of p.nodes)u(G,b.nodes,x,I+1);p.name==="@keyframes"&&x.theme&&l.add(b),(b.nodes.length>0||b.name==="@layer"||b.name==="@charset"||b.name==="@custom-media"||b.name==="@namespace"||b.name==="@import")&&E.push(b)}else if(p.kind==="at-root")for(let b of p.nodes){let G=[];u(b,G,x,0);for(let w of G)j.push(w)}else if(p.kind==="context"){if(p.context.reference)return;for(let b of p.nodes)u(b,E,{...x,...p.context},I)}else p.kind==="comment"&&E.push(p)}let g=[];for(let p of A)u(p,g,{},0);A:for(let[p,E]of n)for(let x of E){if(nt(x.property,e.theme,f)){if(x.property.startsWith(e.theme.prefixKey("--animate-")))for(let b of qe(x.value))c.add(b);continue}let I=p.indexOf(x);if(p.splice(I,1),p.length===0){let b=Gr(g,G=>G.kind==="rule"&&G.nodes===p);if(!b||b.length===0)continue A;b.unshift({kind:"at-root",nodes:g});do{let G=b.pop();if(!G)break;let w=b[b.length-1];if(!w||w.kind!=="at-root"&&w.kind!=="at-rule")break;let P=w.nodes.indexOf(G);if(P===-1)break;w.nodes.splice(P,1)}while(!0);continue A}}for(let p of l)if(!c.has(p.params)){let E=j.indexOf(p);j.splice(E,1)}if(g=g.concat(j),t&2)for(let[p,E]of i)for(let x of E){let I=p.indexOf(x);if(I===-1||x.value==null)continue;let b=W(x.value),G=!1;if(rA(b,(K,{replaceWith:D})=>{if(K.kind!=="function"||K.value!=="color-mix")return;let O=!1,N=!1;if(rA(K.nodes,(M,{replaceWith:L})=>{if(M.kind=="word"&&M.value.toLowerCase()==="currentcolor"){N=!0,G=!0;return}let J=M,tA=null,a=new Set;do{if(J.kind!=="function"||J.value!=="var")return;let d=J.nodes[0];if(!d||d.kind!=="word")return;let m=d.value;if(a.has(m)){O=!0;return}if(a.add(m),G=!0,tA=e.theme.resolveValue(null,[d.value]),!tA){O=!0;return}if(tA.toLowerCase()==="currentcolor"){N=!0;return}tA.startsWith("var(")?J=W(tA)[0]:J=null}while(J);L({kind:"word",value:tA})}),O||N){let M=K.nodes.findIndex(J=>J.kind==="separator"&&J.value.trim().includes(","));if(M===-1)return;let L=K.nodes.length>M?K.nodes[M+1]:null;if(!L)return;D(L)}else if(G){let M=K.nodes[2];M.kind==="word"&&(M.value==="oklab"||M.value==="oklch"||M.value==="lab"||M.value==="lch")&&(M.value="srgb")}}),!G)continue;let w={...x,value:jA(b)},P=Z("@supports (color: color-mix(in lab, red, red))",[x]);P.src=x.src,p.splice(I,1,w,P)}if(t&1){let p=[];if(s.length>0){let E=Z(":root, :host",s);E.src=s[0].src,p.push(E)}if(h.length>0){let E=Z("*, ::before, ::after, ::backdrop",h);E.src=h[0].src,p.push(E)}if(p.length>0){let E=g.findIndex(b=>!(b.kind==="comment"||b.kind==="at-rule"&&(b.name==="@charset"||b.name==="@import"))),x=R("@layer","properties",[]);x.src=p[0].src,g.splice(E<0?g.length:E,0,x);let I=Z("@layer properties",[R("@supports","((-webkit-hyphens: none) and (not (margin-trim: inline))) or ((-moz-orient: inline) and (not (color:rgb(from red r g b))))",p)]);I.src=p[0].src,I.nodes[0].src=p[0].src,g.push(I)}}return g}function EA(A,e){let t=0,j={file:null,code:""};function r(i,l=0){let c="",s="  ".repeat(l);if(i.kind==="declaration"){if(c+=`${s}${i.property}: ${i.value}${i.important?" !important":""};
`,e){t+=s.length;let h=t;t+=i.property.length,t+=2,t+=i.value?.length??0,i.important&&(t+=11);let f=t;t+=2,i.dst=[j,h,f]}}else if(i.kind==="rule"){if(c+=`${s}${i.selector} {
`,e){t+=s.length;let h=t;t+=i.selector.length,t+=1;let f=t;i.dst=[j,h,f],t+=2}for(let h of i.nodes)c+=r(h,l+1);c+=`${s}}
`,e&&(t+=s.length,t+=2)}else if(i.kind==="at-rule"){if(i.nodes.length===0){let h=`${s}${i.name} ${i.params};
`;if(e){t+=s.length;let f=t;t+=i.name.length,t+=1,t+=i.params.length;let u=t;t+=2,i.dst=[j,f,u]}return h}if(c+=`${s}${i.name}${i.params?` ${i.params} `:" "}{
`,e){t+=s.length;let h=t;t+=i.name.length,i.params&&(t+=1,t+=i.params.length),t+=1;let f=t;i.dst=[j,h,f],t+=2}for(let h of i.nodes)c+=r(h,l+1);c+=`${s}}
`,e&&(t+=s.length,t+=2)}else if(i.kind==="comment"){if(c+=`${s}/*${i.value}*/
`,e){t+=s.length;let h=t;t+=2+i.value.length+2;let f=t;i.dst=[j,h,f],t+=1}}else if(i.kind==="context"||i.kind==="at-root")return"";return c}let n="";for(let i of A)n+=r(i,0);return j.code=n,n}function Gr(A,e){let t=[];return z(A,(j,{path:r})=>{if(e(j))return t=[...r],2}),t}function nt(A,e,t,j=new Set){if(j.has(A)||(j.add(A),e.getOptions(A)&24))return!0;{let r=t.get(A)??[];for(let n of r)if(nt(n,e,t,j))return!0}return!1}function qe(A){return A.split(/[\s,]+/)}function bA(A){if(A.indexOf("(")===-1)return xA(A);let e=W(A);return vj(e),A=jA(e),A=oe(A),A}function xA(A,e=!1){let t="";for(let j=0;j<A.length;j++){let r=A[j];r==="\\"&&A[j+1]==="_"?(t+="_",j+=1):r==="_"&&!e?t+=" ":t+=r}return t}function vj(A){for(let e of A)switch(e.kind){case"function":{if(e.value==="url"||e.value.endsWith("_url")){e.value=xA(e.value);break}if(e.value==="var"||e.value.endsWith("_var")||e.value==="theme"||e.value.endsWith("_theme")){e.value=xA(e.value);for(let t=0;t<e.nodes.length;t++){if(t==0&&e.nodes[t].kind==="word"){e.nodes[t].value=xA(e.nodes[t].value,!0);continue}vj([e.nodes[t]])}break}e.value=xA(e.value),vj(e.nodes);break}case"separator":case"word":{e.value=xA(e.value);break}default:Fr(e)}}function Fr(A){throw new Error(`Unexpected value: ${A}`)}var Ej=new Uint8Array(256);function uA(A){let e=0,t=A.length;for(let j=0;j<t;j++){let r=A.charCodeAt(j);switch(r){case 92:j+=1;break;case 39:case 34:for(;++j<t;){let n=A.charCodeAt(j);if(n===92){j+=1;continue}if(n===r)break}break;case 40:Ej[e]=41,e++;break;case 91:Ej[e]=93,e++;break;case 123:break;case 93:case 125:case 41:if(e===0)return!1;e>0&&r===Ej[e-1]&&e--;break;case 59:if(e===0)return!1;break}}return!0}var wr=58,Ge=45,Fe=97,we=122;function*yr(A,e){let t=H(A,":");if(e.theme.prefix){if(t.length===1||t[0]!==e.theme.prefix)return null;t.shift()}let j=t.pop(),r=[];for(let f=t.length-1;f>=0;--f){let u=e.parseVariant(t[f]);if(u===null)return;r.push(u)}let n=!1;j[j.length-1]==="!"?(n=!0,j=j.slice(0,-1)):j[0]==="!"&&(n=!0,j=j.slice(1)),e.utilities.has(j,"static")&&!j.includes("[")&&(yield{kind:"static",root:j,variants:r,important:n,raw:A});let[i,l=null,c]=H(j,"/");if(c)return;let s=l===null?null:$j(l);if(l!==null&&s===null)return;if(i[0]==="["){if(i[i.length-1]!=="]")return;let f=i.charCodeAt(1);if(f!==Ge&&!(f>=Fe&&f<=we))return;i=i.slice(1,-1);let u=i.indexOf(":");if(u===-1||u===0||u===i.length-1)return;let g=i.slice(0,u),p=bA(i.slice(u+1));if(!uA(p))return;yield{kind:"arbitrary",property:g,value:p,modifier:s,variants:r,important:n,raw:A};return}let h;if(i[i.length-1]==="]"){let f=i.indexOf("-[");if(f===-1)return;let u=i.slice(0,f);if(!e.utilities.has(u,"functional"))return;let g=i.slice(f+1);h=[[u,g]]}else if(i[i.length-1]===")"){let f=i.indexOf("-(");if(f===-1)return;let u=i.slice(0,f);if(!e.utilities.has(u,"functional"))return;let g=i.slice(f+2,-1),p=H(g,":"),E=null;if(p.length===2&&(E=p[0],g=p[1]),g[0]!=="-"||g[1]!=="-"||!uA(g))return;h=[[u,E===null?`[var(${g})]`:`[${E}:var(${g})]`]]}else h=st(i,f=>e.utilities.has(f,"functional"));for(let[f,u]of h){let g={kind:"functional",root:f,modifier:s,value:null,variants:r,important:n,raw:A};if(u===null){yield g;continue}{let p=u.indexOf("[");if(p!==-1){if(u[u.length-1]!=="]")return;let E=bA(u.slice(p+1,-1));if(!uA(E))continue;let x="";for(let I=0;I<E.length;I++){let b=E.charCodeAt(I);if(b===wr){x=E.slice(0,I),E=E.slice(I+1);break}if(!(b===Ge||b>=Fe&&b<=we))break}if(E.length===0||E.trim().length===0)continue;g.value={kind:"arbitrary",dataType:x||null,value:E}}else{let E=l===null||g.modifier?.kind==="arbitrary"?null:`${u}/${l}`;g.value={kind:"named",value:u,fraction:E}}}yield g}}function $j(A){if(A[0]==="["&&A[A.length-1]==="]"){let e=bA(A.slice(1,-1));return!uA(e)||e.length===0||e.trim().length===0?null:{kind:"arbitrary",value:e}}return A[0]==="("&&A[A.length-1]===")"?(A=A.slice(1,-1),A[0]!=="-"||A[1]!=="-"||!uA(A)?null:(A=`var(${A})`,{kind:"arbitrary",value:bA(A)})):{kind:"named",value:A}}function Or(A,e){if(A[0]==="["&&A[A.length-1]==="]"){if(A[1]==="@"&&A.includes("&"))return null;let t=bA(A.slice(1,-1));if(!uA(t)||t.length===0||t.trim().length===0)return null;let j=t[0]===">"||t[0]==="+"||t[0]==="~";return!j&&t[0]!=="@"&&!t.includes("&")&&(t=`&:is(${t})`),{kind:"arbitrary",selector:t,relative:j}}{let[t,j=null,r]=H(A,"/");if(r)return null;let n=st(t,i=>e.variants.has(i));for(let[i,l]of n)switch(e.variants.kind(i)){case"static":return l!==null||j!==null?null:{kind:"static",root:i};case"functional":{let c=j===null?null:$j(j);if(j!==null&&c===null)return null;if(l===null)return{kind:"functional",root:i,modifier:c,value:null};if(l[l.length-1]==="]"){if(l[0]!=="[")continue;let s=bA(l.slice(1,-1));return!uA(s)||s.length===0||s.trim().length===0?null:{kind:"functional",root:i,modifier:c,value:{kind:"arbitrary",value:s}}}if(l[l.length-1]===")"){if(l[0]!=="(")continue;let s=bA(l.slice(1,-1));return!uA(s)||s.length===0||s.trim().length===0||s[0]!=="-"||s[1]!=="-"?null:{kind:"functional",root:i,modifier:c,value:{kind:"arbitrary",value:`var(${s})`}}}return{kind:"functional",root:i,modifier:c,value:{kind:"named",value:l}}}case"compound":{if(l===null)return null;let c=e.parseVariant(l);if(c===null||!e.variants.compoundsWith(i,c))return null;let s=j===null?null:$j(j);return j!==null&&s===null?null:{kind:"compound",root:i,modifier:s,variant:c}}}}return null}function*st(A,e){e(A)&&(yield[A,null]);let t=A.lastIndexOf("-");for(;t>0;){let j=A.slice(0,t);if(e(j)){let r=[j,A.slice(t+1)];if(r[1]==="")break;yield r}t=A.lastIndexOf("-",t-1)}A[0]==="@"&&e("@")&&(yield["@",A.slice(1)])}function Kr(A,e){let t=[];for(let r of e.variants)t.unshift(Dj(r));A.theme.prefix&&t.unshift(A.theme.prefix);let j="";if(e.kind==="static"&&(j+=e.root),e.kind==="functional"&&(j+=e.root,e.value))if(e.value.kind==="arbitrary"){if(e.value!==null){let r=Mj(e.value.value),n=r?e.value.value.slice(4,-1):e.value.value,[i,l]=r?["(",")"]:["[","]"];e.value.dataType?j+=`-${i}${e.value.dataType}:${_A(n)}${l}`:j+=`-${i}${_A(n)}${l}`}}else e.value.kind==="named"&&(j+=`-${e.value.value}`);return e.kind==="arbitrary"&&(j+=`[${e.property}:${_A(e.value)}]`),(e.kind==="arbitrary"||e.kind==="functional")&&(j+=lt(e.modifier)),e.important&&(j+="!"),t.push(j),t.join(":")}function lt(A){if(A===null)return"";let e=Mj(A.value),t=e?A.value.slice(4,-1):A.value,[j,r]=e?["(",")"]:["[","]"];return A.kind==="arbitrary"?`/${j}${_A(t)}${r}`:A.kind==="named"?`/${A.value}`:""}function Dj(A){if(A.kind==="static")return A.root;if(A.kind==="arbitrary")return`[${_A(Mr(A.selector))}]`;let e="";if(A.kind==="functional"){e+=A.root;let t=A.root!=="@";if(A.value)if(A.value.kind==="arbitrary"){let j=Mj(A.value.value),r=j?A.value.value.slice(4,-1):A.value.value,[n,i]=j?["(",")"]:["[","]"];e+=`${t?"-":""}${n}${_A(r)}${i}`}else A.value.kind==="named"&&(e+=`${t?"-":""}${A.value.value}`)}return A.kind==="compound"&&(e+=A.root,e+="-",e+=Dj(A.variant)),(A.kind==="functional"||A.kind==="compound")&&(e+=lt(A.modifier)),e}var Pr=new U(A=>{let e=W(A),t=new Set;return rA(e,(j,{parent:r})=>{let n=r===null?e:r.nodes??[];if(j.kind==="word"&&(j.value==="+"||j.value==="-"||j.value==="*"||j.value==="/")){let i=n.indexOf(j)??-1;if(i===-1)return;let l=n[i-1];if(l?.kind!=="separator"||l.value!==" ")return;let c=n[i+1];if(c?.kind!=="separator"||c.value!==" ")return;t.add(l),t.add(c)}else j.kind==="separator"&&j.value.trim()==="/"?j.value="/":j.kind==="separator"&&j.value.length>0&&j.value.trim()===""?(n[0]===j||n[n.length-1]===j)&&t.add(j):j.kind==="separator"&&j.value.trim()===","&&(j.value=",")}),t.size>0&&rA(e,(j,{replaceWith:r})=>{t.has(j)&&(t.delete(j),r([]))}),Ij(e),jA(e)});function _A(A){return Pr.get(A)}var Dr=new U(A=>{let e=W(A);return e.length===3&&e[0].kind==="word"&&e[0].value==="&"&&e[1].kind==="separator"&&e[1].value===":"&&e[2].kind==="function"&&e[2].value==="is"?jA(e[2].nodes):A});function Mr(A){return Dr.get(A)}function Ij(A){for(let e of A)switch(e.kind){case"function":{if(e.value==="url"||e.value.endsWith("_url")){e.value=qA(e.value);break}if(e.value==="var"||e.value.endsWith("_var")||e.value==="theme"||e.value.endsWith("_theme")){e.value=qA(e.value);for(let t=0;t<e.nodes.length;t++)Ij([e.nodes[t]]);break}e.value=qA(e.value),Ij(e.nodes);break}case"separator":e.value=qA(e.value);break;case"word":{(e.value[0]!=="-"||e.value[1]!=="-")&&(e.value=qA(e.value));break}default:Hr(e)}}var Tr=new U(A=>{let e=W(A);return e.length===1&&e[0].kind==="function"&&e[0].value==="var"});function Mj(A){return Tr.get(A)}function Hr(A){throw new Error(`Unexpected value: ${A}`)}function qA(A){return A.replaceAll("_",String.raw`\_`).replaceAll(" ","_")}function jj(A,e,t){if(A===e)return 0;let j=A.indexOf("("),r=e.indexOf("("),n=j===-1?A.replace(/[\d.]+/g,""):A.slice(0,j),i=r===-1?e.replace(/[\d.]+/g,""):e.slice(0,r),l=(n===i?0:n<i?-1:1)||(t==="asc"?parseInt(A)-parseInt(e):parseInt(e)-parseInt(A));return Number.isNaN(l)?A<e?-1:1:l}var Lr=new Set(["inset","inherit","initial","revert","unset"]),ye=/^-?(\d+|\.\d+)(.*?)$/g;function ej(A,e){return H(A,",").map(t=>{t=t.trim();let j=H(t," ").filter(c=>c.trim()!==""),r=null,n=null,i=null;for(let c of j)Lr.has(c)||(ye.test(c)?(n===null?n=c:i===null&&(i=c),ye.lastIndex=0):r===null&&(r=c));if(n===null||i===null)return t;let l=e(r??"currentcolor");return r!==null?t.replace(r,l):`${t} ${l}`}).join(", ")}var Sr=/^-?[a-z][a-zA-Z0-9/%._-]*$/,Cr=/^-?[a-z][a-zA-Z0-9/%._-]*-\*$/,tj=["0","0.5","1","1.5","2","2.5","3","3.5","4","5","6","7","8","9","10","11","12","14","16","20","24","28","32","36","40","44","48","52","56","60","64","72","80","96"],zr=class{constructor(){nA(this,"utilities",new U(()=>[]));nA(this,"completions",new Map)}static(A,e){this.utilities.get(A).push({kind:"static",compileFn:e})}functional(A,e,t){this.utilities.get(A).push({kind:"functional",compileFn:e,options:t})}has(A,e){return this.utilities.has(A)&&this.utilities.get(A).some(t=>t.kind===e)}get(A){return this.utilities.has(A)?this.utilities.get(A):[]}getCompletions(A){return this.completions.get(A)?.()??[]}suggest(A,e){this.completions.set(A,e)}keys(A){let e=[];for(let[t,j]of this.utilities.entries())for(let r of j)if(r.kind===A){e.push(t);break}return e}};function _(A,e,t){return R("@property",A,[o("syntax",t?`"${t}"`:'"*"'),o("inherits","false"),...e?[o("initial-value",e)]:[]])}function AA(A,e){if(e===null)return A;let t=Number(e);return Number.isNaN(t)||(e=`${t*100}%`),e==="100%"?A:`color-mix(in oklab, ${A} ${e}, transparent)`}function dt(A,e){let t=Number(e);return Number.isNaN(t)||(e=`${t*100}%`),`oklab(from ${A} l a b / ${e})`}function Y(A,e,t){if(!e)return A;if(e.kind==="arbitrary")return AA(A,e.value);let j=t.resolve(e.value,["--opacity"]);return j?AA(A,j):NA(e.value)?AA(A,`${e.value}%`):null}function Q(A,e,t){let j=null;switch(A.value.value){case"inherit":{j="inherit";break}case"transparent":{j="transparent";break}case"current":{j="currentcolor";break}default:{j=e.resolve(A.value.value,t);break}}return j?Y(j,A.modifier,e):null}var ft=/(\d+)_(\d+)/g;function Nr(A){let e=new zr;function t(a,d){function*m(k){for(let v of A.keysInNamespaces(k))yield v.replace(ft,(y,$,q)=>`${$}.${q}`)}let B=["1/2","1/3","2/3","1/4","2/4","3/4","1/5","2/5","3/5","4/5","1/6","2/6","3/6","4/6","5/6","1/12","2/12","3/12","4/12","5/12","6/12","7/12","8/12","9/12","10/12","11/12"];e.suggest(a,()=>{let k=[];for(let v of d()){if(typeof v=="string"){k.push({values:[v],modifiers:[]});continue}let y=[...v.values??[],...m(v.valueThemeKeys??[])],$=[...v.modifiers??[],...m(v.modifierThemeKeys??[])];v.supportsFractions&&y.push(...B),v.hasDefaultValue&&y.unshift(null),k.push({supportsNegative:v.supportsNegative,values:y,modifiers:$})}return k})}function j(a,d){e.static(a,()=>d.map(m=>typeof m=="function"?m():o(m[0],m[1])))}function r(a,d){function m({negative:B}){return k=>{let v=null,y=null;if(k.value)if(k.value.kind==="arbitrary"){if(k.modifier)return;v=k.value.value,y=k.value.dataType}else{if(v=A.resolve(k.value.fraction??k.value.value,d.themeKeys??[]),v===null&&d.supportsFractions&&k.value.fraction){let[$,q]=H(k.value.fraction,"/");if(!F($)||!F(q))return;v=`calc(${k.value.fraction} * 100%)`}if(v===null&&B&&d.handleNegativeBareValue){if(v=d.handleNegativeBareValue(k.value),!v?.includes("/")&&k.modifier)return;if(v!==null)return d.handle(v,null)}if(v===null&&d.handleBareValue&&(v=d.handleBareValue(k.value),!v?.includes("/")&&k.modifier))return}else{if(k.modifier)return;v=d.defaultValue!==void 0?d.defaultValue:A.resolve(null,d.themeKeys??[])}if(v!==null)return d.handle(B?`calc(${v} * -1)`:v,y)}}d.supportsNegative&&e.functional(`-${a}`,m({negative:!0})),e.functional(a,m({negative:!1})),t(a,()=>[{supportsNegative:d.supportsNegative,valueThemeKeys:d.themeKeys??[],hasDefaultValue:d.defaultValue!==void 0&&d.defaultValue!==null,supportsFractions:d.supportsFractions}])}function n(a,d){e.functional(a,m=>{if(!m.value)return;let B=null;if(m.value.kind==="arbitrary"?(B=m.value.value,B=Y(B,m.modifier,A)):B=Q(m,A,d.themeKeys),B!==null)return d.handle(B)}),t(a,()=>[{values:["current","inherit","transparent"],valueThemeKeys:d.themeKeys,modifiers:Array.from({length:21},(m,B)=>`${B*5}`)}])}function i(a,d,m,{supportsNegative:B=!1,supportsFractions:k=!1}={}){B&&e.static(`-${a}-px`,()=>m("-1px")),e.static(`${a}-px`,()=>m("1px")),r(a,{themeKeys:d,supportsFractions:k,supportsNegative:B,defaultValue:null,handleBareValue:({value:v})=>{let y=A.resolve(null,["--spacing"]);return!y||!pA(v)?null:`calc(${y} * ${v})`},handleNegativeBareValue:({value:v})=>{let y=A.resolve(null,["--spacing"]);return!y||!pA(v)?null:`calc(${y} * -${v})`},handle:m}),t(a,()=>[{values:A.get(["--spacing"])?tj:[],supportsNegative:B,supportsFractions:k,valueThemeKeys:d}])}j("sr-only",[["position","absolute"],["width","1px"],["height","1px"],["padding","0"],["margin","-1px"],["overflow","hidden"],["clip","rect(0, 0, 0, 0)"],["white-space","nowrap"],["border-width","0"]]),j("not-sr-only",[["position","static"],["width","auto"],["height","auto"],["padding","0"],["margin","0"],["overflow","visible"],["clip","auto"],["white-space","normal"]]),j("pointer-events-none",[["pointer-events","none"]]),j("pointer-events-auto",[["pointer-events","auto"]]),j("visible",[["visibility","visible"]]),j("invisible",[["visibility","hidden"]]),j("collapse",[["visibility","collapse"]]),j("static",[["position","static"]]),j("fixed",[["position","fixed"]]),j("absolute",[["position","absolute"]]),j("relative",[["position","relative"]]),j("sticky",[["position","sticky"]]);for(let[a,d]of[["inset","inset"],["inset-x","inset-inline"],["inset-y","inset-block"],["start","inset-inline-start"],["end","inset-inline-end"],["top","top"],["right","right"],["bottom","bottom"],["left","left"]])j(`${a}-auto`,[[d,"auto"]]),j(`${a}-full`,[[d,"100%"]]),j(`-${a}-full`,[[d,"-100%"]]),i(a,["--inset","--spacing"],m=>[o(d,m)],{supportsNegative:!0,supportsFractions:!0});j("isolate",[["isolation","isolate"]]),j("isolation-auto",[["isolation","auto"]]),j("z-auto",[["z-index","auto"]]),r("z",{supportsNegative:!0,handleBareValue:({value:a})=>F(a)?a:null,themeKeys:["--z-index"],handle:a=>[o("z-index",a)]}),t("z",()=>[{supportsNegative:!0,values:["0","10","20","30","40","50"],valueThemeKeys:["--z-index"]}]),j("order-first",[["order","-9999"]]),j("order-last",[["order","9999"]]),r("order",{supportsNegative:!0,handleBareValue:({value:a})=>F(a)?a:null,themeKeys:["--order"],handle:a=>[o("order",a)]}),t("order",()=>[{supportsNegative:!0,values:Array.from({length:12},(a,d)=>`${d+1}`),valueThemeKeys:["--order"]}]),j("col-auto",[["grid-column","auto"]]),r("col",{supportsNegative:!0,handleBareValue:({value:a})=>F(a)?a:null,themeKeys:["--grid-column"],handle:a=>[o("grid-column",a)]}),j("col-span-full",[["grid-column","1 / -1"]]),r("col-span",{handleBareValue:({value:a})=>F(a)?a:null,handle:a=>[o("grid-column",`span ${a} / span ${a}`)]}),j("col-start-auto",[["grid-column-start","auto"]]),r("col-start",{supportsNegative:!0,handleBareValue:({value:a})=>F(a)?a:null,themeKeys:["--grid-column-start"],handle:a=>[o("grid-column-start",a)]}),j("col-end-auto",[["grid-column-end","auto"]]),r("col-end",{supportsNegative:!0,handleBareValue:({value:a})=>F(a)?a:null,themeKeys:["--grid-column-end"],handle:a=>[o("grid-column-end",a)]}),t("col-span",()=>[{values:Array.from({length:12},(a,d)=>`${d+1}`),valueThemeKeys:[]}]),t("col-start",()=>[{supportsNegative:!0,values:Array.from({length:13},(a,d)=>`${d+1}`),valueThemeKeys:["--grid-column-start"]}]),t("col-end",()=>[{supportsNegative:!0,values:Array.from({length:13},(a,d)=>`${d+1}`),valueThemeKeys:["--grid-column-end"]}]),j("row-auto",[["grid-row","auto"]]),r("row",{supportsNegative:!0,handleBareValue:({value:a})=>F(a)?a:null,themeKeys:["--grid-row"],handle:a=>[o("grid-row",a)]}),j("row-span-full",[["grid-row","1 / -1"]]),r("row-span",{themeKeys:[],handleBareValue:({value:a})=>F(a)?a:null,handle:a=>[o("grid-row",`span ${a} / span ${a}`)]}),j("row-start-auto",[["grid-row-start","auto"]]),r("row-start",{supportsNegative:!0,handleBareValue:({value:a})=>F(a)?a:null,themeKeys:["--grid-row-start"],handle:a=>[o("grid-row-start",a)]}),j("row-end-auto",[["grid-row-end","auto"]]),r("row-end",{supportsNegative:!0,handleBareValue:({value:a})=>F(a)?a:null,themeKeys:["--grid-row-end"],handle:a=>[o("grid-row-end",a)]}),t("row-span",()=>[{values:Array.from({length:12},(a,d)=>`${d+1}`),valueThemeKeys:[]}]),t("row-start",()=>[{supportsNegative:!0,values:Array.from({length:13},(a,d)=>`${d+1}`),valueThemeKeys:["--grid-row-start"]}]),t("row-end",()=>[{supportsNegative:!0,values:Array.from({length:13},(a,d)=>`${d+1}`),valueThemeKeys:["--grid-row-end"]}]),j("float-start",[["float","inline-start"]]),j("float-end",[["float","inline-end"]]),j("float-right",[["float","right"]]),j("float-left",[["float","left"]]),j("float-none",[["float","none"]]),j("clear-start",[["clear","inline-start"]]),j("clear-end",[["clear","inline-end"]]),j("clear-right",[["clear","right"]]),j("clear-left",[["clear","left"]]),j("clear-both",[["clear","both"]]),j("clear-none",[["clear","none"]]);for(let[a,d]of[["m","margin"],["mx","margin-inline"],["my","margin-block"],["ms","margin-inline-start"],["me","margin-inline-end"],["mt","margin-top"],["mr","margin-right"],["mb","margin-bottom"],["ml","margin-left"]])j(`${a}-auto`,[[d,"auto"]]),i(a,["--margin","--spacing"],m=>[o(d,m)],{supportsNegative:!0});j("box-border",[["box-sizing","border-box"]]),j("box-content",[["box-sizing","content-box"]]),j("line-clamp-none",[["overflow","visible"],["display","block"],["-webkit-box-orient","horizontal"],["-webkit-line-clamp","unset"]]),r("line-clamp",{themeKeys:["--line-clamp"],handleBareValue:({value:a})=>F(a)?a:null,handle:a=>[o("overflow","hidden"),o("display","-webkit-box"),o("-webkit-box-orient","vertical"),o("-webkit-line-clamp",a)]}),t("line-clamp",()=>[{values:["1","2","3","4","5","6"],valueThemeKeys:["--line-clamp"]}]),j("block",[["display","block"]]),j("inline-block",[["display","inline-block"]]),j("inline",[["display","inline"]]),j("hidden",[["display","none"]]),j("inline-flex",[["display","inline-flex"]]),j("table",[["display","table"]]),j("inline-table",[["display","inline-table"]]),j("table-caption",[["display","table-caption"]]),j("table-cell",[["display","table-cell"]]),j("table-column",[["display","table-column"]]),j("table-column-group",[["display","table-column-group"]]),j("table-footer-group",[["display","table-footer-group"]]),j("table-header-group",[["display","table-header-group"]]),j("table-row-group",[["display","table-row-group"]]),j("table-row",[["display","table-row"]]),j("flow-root",[["display","flow-root"]]),j("flex",[["display","flex"]]),j("grid",[["display","grid"]]),j("inline-grid",[["display","inline-grid"]]),j("contents",[["display","contents"]]),j("list-item",[["display","list-item"]]),j("field-sizing-content",[["field-sizing","content"]]),j("field-sizing-fixed",[["field-sizing","fixed"]]),j("aspect-auto",[["aspect-ratio","auto"]]),j("aspect-square",[["aspect-ratio","1 / 1"]]),r("aspect",{themeKeys:["--aspect"],handleBareValue:({fraction:a})=>{if(a===null)return null;let[d,m]=H(a,"/");return!F(d)||!F(m)?null:a},handle:a=>[o("aspect-ratio",a)]});for(let[a,d]of[["full","100%"],["svw","100svw"],["lvw","100lvw"],["dvw","100dvw"],["svh","100svh"],["lvh","100lvh"],["dvh","100dvh"],["min","min-content"],["max","max-content"],["fit","fit-content"]])j(`size-${a}`,[["--tw-sort","size"],["width",d],["height",d]]),j(`w-${a}`,[["width",d]]),j(`h-${a}`,[["height",d]]),j(`min-w-${a}`,[["min-width",d]]),j(`min-h-${a}`,[["min-height",d]]),j(`max-w-${a}`,[["max-width",d]]),j(`max-h-${a}`,[["max-height",d]]);j("size-auto",[["--tw-sort","size"],["width","auto"],["height","auto"]]),j("w-auto",[["width","auto"]]),j("h-auto",[["height","auto"]]),j("min-w-auto",[["min-width","auto"]]),j("min-h-auto",[["min-height","auto"]]),j("h-lh",[["height","1lh"]]),j("min-h-lh",[["min-height","1lh"]]),j("max-h-lh",[["max-height","1lh"]]),j("w-screen",[["width","100vw"]]),j("min-w-screen",[["min-width","100vw"]]),j("max-w-screen",[["max-width","100vw"]]),j("h-screen",[["height","100vh"]]),j("min-h-screen",[["min-height","100vh"]]),j("max-h-screen",[["max-height","100vh"]]),j("max-w-none",[["max-width","none"]]),j("max-h-none",[["max-height","none"]]),i("size",["--size","--spacing"],a=>[o("--tw-sort","size"),o("width",a),o("height",a)],{supportsFractions:!0});for(let[a,d,m]of[["w",["--width","--spacing","--container"],"width"],["min-w",["--min-width","--spacing","--container"],"min-width"],["max-w",["--max-width","--spacing","--container"],"max-width"],["h",["--height","--spacing"],"height"],["min-h",["--min-height","--height","--spacing"],"min-height"],["max-h",["--max-height","--height","--spacing"],"max-height"]])i(a,d,B=>[o(m,B)],{supportsFractions:!0});e.static("container",()=>{let a=[...A.namespace("--breakpoint").values()];a.sort((m,B)=>jj(m,B,"asc"));let d=[o("--tw-sort","--tw-container-component"),o("width","100%")];for(let m of a)d.push(R("@media",`(width >= ${m})`,[o("max-width",m)]));return d}),j("flex-auto",[["flex","auto"]]),j("flex-initial",[["flex","0 auto"]]),j("flex-none",[["flex","none"]]),e.functional("flex",a=>{if(a.value){if(a.value.kind==="arbitrary")return a.modifier?void 0:[o("flex",a.value.value)];if(a.value.fraction){let[d,m]=H(a.value.fraction,"/");return!F(d)||!F(m)?void 0:[o("flex",`calc(${a.value.fraction} * 100%)`)]}if(F(a.value.value))return a.modifier?void 0:[o("flex",a.value.value)]}}),t("flex",()=>[{supportsFractions:!0},{values:Array.from({length:12},(a,d)=>`${d+1}`)}]),r("shrink",{defaultValue:"1",handleBareValue:({value:a})=>F(a)?a:null,handle:a=>[o("flex-shrink",a)]}),r("grow",{defaultValue:"1",handleBareValue:({value:a})=>F(a)?a:null,handle:a=>[o("flex-grow",a)]}),t("shrink",()=>[{values:["0"],valueThemeKeys:[],hasDefaultValue:!0}]),t("grow",()=>[{values:["0"],valueThemeKeys:[],hasDefaultValue:!0}]),j("basis-auto",[["flex-basis","auto"]]),j("basis-full",[["flex-basis","100%"]]),i("basis",["--flex-basis","--spacing","--container"],a=>[o("flex-basis",a)],{supportsFractions:!0}),j("table-auto",[["table-layout","auto"]]),j("table-fixed",[["table-layout","fixed"]]),j("caption-top",[["caption-side","top"]]),j("caption-bottom",[["caption-side","bottom"]]),j("border-collapse",[["border-collapse","collapse"]]),j("border-separate",[["border-collapse","separate"]]);let l=()=>S([_("--tw-border-spacing-x","0","<length>"),_("--tw-border-spacing-y","0","<length>")]);i("border-spacing",["--border-spacing","--spacing"],a=>[l(),o("--tw-border-spacing-x",a),o("--tw-border-spacing-y",a),o("border-spacing","var(--tw-border-spacing-x) var(--tw-border-spacing-y)")]),i("border-spacing-x",["--border-spacing","--spacing"],a=>[l(),o("--tw-border-spacing-x",a),o("border-spacing","var(--tw-border-spacing-x) var(--tw-border-spacing-y)")]),i("border-spacing-y",["--border-spacing","--spacing"],a=>[l(),o("--tw-border-spacing-y",a),o("border-spacing","var(--tw-border-spacing-x) var(--tw-border-spacing-y)")]),j("origin-center",[["transform-origin","center"]]),j("origin-top",[["transform-origin","top"]]),j("origin-top-right",[["transform-origin","top right"]]),j("origin-right",[["transform-origin","right"]]),j("origin-bottom-right",[["transform-origin","bottom right"]]),j("origin-bottom",[["transform-origin","bottom"]]),j("origin-bottom-left",[["transform-origin","bottom left"]]),j("origin-left",[["transform-origin","left"]]),j("origin-top-left",[["transform-origin","top left"]]),r("origin",{themeKeys:["--transform-origin"],handle:a=>[o("transform-origin",a)]}),j("perspective-origin-center",[["perspective-origin","center"]]),j("perspective-origin-top",[["perspective-origin","top"]]),j("perspective-origin-top-right",[["perspective-origin","top right"]]),j("perspective-origin-right",[["perspective-origin","right"]]),j("perspective-origin-bottom-right",[["perspective-origin","bottom right"]]),j("perspective-origin-bottom",[["perspective-origin","bottom"]]),j("perspective-origin-bottom-left",[["perspective-origin","bottom left"]]),j("perspective-origin-left",[["perspective-origin","left"]]),j("perspective-origin-top-left",[["perspective-origin","top left"]]),r("perspective-origin",{themeKeys:["--perspective-origin"],handle:a=>[o("perspective-origin",a)]}),j("perspective-none",[["perspective","none"]]),r("perspective",{themeKeys:["--perspective"],handle:a=>[o("perspective",a)]});let c=()=>S([_("--tw-translate-x","0"),_("--tw-translate-y","0"),_("--tw-translate-z","0")]);j("translate-none",[["translate","none"]]),j("-translate-full",[c,["--tw-translate-x","-100%"],["--tw-translate-y","-100%"],["translate","var(--tw-translate-x) var(--tw-translate-y)"]]),j("translate-full",[c,["--tw-translate-x","100%"],["--tw-translate-y","100%"],["translate","var(--tw-translate-x) var(--tw-translate-y)"]]),i("translate",["--translate","--spacing"],a=>[c(),o("--tw-translate-x",a),o("--tw-translate-y",a),o("translate","var(--tw-translate-x) var(--tw-translate-y)")],{supportsNegative:!0,supportsFractions:!0});for(let a of["x","y"])j(`-translate-${a}-full`,[c,[`--tw-translate-${a}`,"-100%"],["translate","var(--tw-translate-x) var(--tw-translate-y)"]]),j(`translate-${a}-full`,[c,[`--tw-translate-${a}`,"100%"],["translate","var(--tw-translate-x) var(--tw-translate-y)"]]),i(`translate-${a}`,["--translate","--spacing"],d=>[c(),o(`--tw-translate-${a}`,d),o("translate","var(--tw-translate-x) var(--tw-translate-y)")],{supportsNegative:!0,supportsFractions:!0});i("translate-z",["--translate","--spacing"],a=>[c(),o("--tw-translate-z",a),o("translate","var(--tw-translate-x) var(--tw-translate-y) var(--tw-translate-z)")],{supportsNegative:!0}),j("translate-3d",[c,["translate","var(--tw-translate-x) var(--tw-translate-y) var(--tw-translate-z)"]]);let s=()=>S([_("--tw-scale-x","1"),_("--tw-scale-y","1"),_("--tw-scale-z","1")]);j("scale-none",[["scale","none"]]);function h({negative:a}){return d=>{if(!d.value||d.modifier)return;let m;return d.value.kind==="arbitrary"?(m=d.value.value,m=a?`calc(${m} * -1)`:m,[o("scale",m)]):(m=A.resolve(d.value.value,["--scale"]),!m&&F(d.value.value)&&(m=`${d.value.value}%`),m?(m=a?`calc(${m} * -1)`:m,[s(),o("--tw-scale-x",m),o("--tw-scale-y",m),o("--tw-scale-z",m),o("scale","var(--tw-scale-x) var(--tw-scale-y)")]):void 0)}}e.functional("-scale",h({negative:!0})),e.functional("scale",h({negative:!1})),t("scale",()=>[{supportsNegative:!0,values:["0","50","75","90","95","100","105","110","125","150","200"],valueThemeKeys:["--scale"]}]);for(let a of["x","y","z"])r(`scale-${a}`,{supportsNegative:!0,themeKeys:["--scale"],handleBareValue:({value:d})=>F(d)?`${d}%`:null,handle:d=>[s(),o(`--tw-scale-${a}`,d),o("scale",`var(--tw-scale-x) var(--tw-scale-y)${a==="z"?" var(--tw-scale-z)":""}`)]}),t(`scale-${a}`,()=>[{supportsNegative:!0,values:["0","50","75","90","95","100","105","110","125","150","200"],valueThemeKeys:["--scale"]}]);j("scale-3d",[s,["scale","var(--tw-scale-x) var(--tw-scale-y) var(--tw-scale-z)"]]),j("rotate-none",[["rotate","none"]]);function f({negative:a}){return d=>{if(!d.value||d.modifier)return;let m;if(d.value.kind==="arbitrary"){m=d.value.value;let B=d.value.dataType??V(m,["angle","vector"]);if(B==="vector")return[o("rotate",`${m} var(--tw-rotate)`)];if(B!=="angle")return[o("rotate",a?`calc(${m} * -1)`:m)]}else if(m=A.resolve(d.value.value,["--rotate"]),!m&&F(d.value.value)&&(m=`${d.value.value}deg`),!m)return;return[o("rotate",a?`calc(${m} * -1)`:m)]}}e.functional("-rotate",f({negative:!0})),e.functional("rotate",f({negative:!1})),t("rotate",()=>[{supportsNegative:!0,values:["0","1","2","3","6","12","45","90","180"],valueThemeKeys:["--rotate"]}]);{let a=["var(--tw-rotate-x,)","var(--tw-rotate-y,)","var(--tw-rotate-z,)","var(--tw-skew-x,)","var(--tw-skew-y,)"].join(" "),d=()=>S([_("--tw-rotate-x"),_("--tw-rotate-y"),_("--tw-rotate-z"),_("--tw-skew-x"),_("--tw-skew-y")]);for(let m of["x","y","z"])r(`rotate-${m}`,{supportsNegative:!0,themeKeys:["--rotate"],handleBareValue:({value:B})=>F(B)?`${B}deg`:null,handle:B=>[d(),o(`--tw-rotate-${m}`,`rotate${m.toUpperCase()}(${B})`),o("transform",a)]}),t(`rotate-${m}`,()=>[{supportsNegative:!0,values:["0","1","2","3","6","12","45","90","180"],valueThemeKeys:["--rotate"]}]);r("skew",{supportsNegative:!0,themeKeys:["--skew"],handleBareValue:({value:m})=>F(m)?`${m}deg`:null,handle:m=>[d(),o("--tw-skew-x",`skewX(${m})`),o("--tw-skew-y",`skewY(${m})`),o("transform",a)]}),r("skew-x",{supportsNegative:!0,themeKeys:["--skew"],handleBareValue:({value:m})=>F(m)?`${m}deg`:null,handle:m=>[d(),o("--tw-skew-x",`skewX(${m})`),o("transform",a)]}),r("skew-y",{supportsNegative:!0,themeKeys:["--skew"],handleBareValue:({value:m})=>F(m)?`${m}deg`:null,handle:m=>[d(),o("--tw-skew-y",`skewY(${m})`),o("transform",a)]}),t("skew",()=>[{supportsNegative:!0,values:["0","1","2","3","6","12"],valueThemeKeys:["--skew"]}]),t("skew-x",()=>[{supportsNegative:!0,values:["0","1","2","3","6","12"],valueThemeKeys:["--skew"]}]),t("skew-y",()=>[{supportsNegative:!0,values:["0","1","2","3","6","12"],valueThemeKeys:["--skew"]}]),e.functional("transform",m=>{if(m.modifier)return;let B=null;if(m.value?m.value.kind==="arbitrary"&&(B=m.value.value):B=a,B!==null)return[d(),o("transform",B)]}),t("transform",()=>[{hasDefaultValue:!0}]),j("transform-cpu",[["transform",a]]),j("transform-gpu",[["transform",`translateZ(0) ${a}`]]),j("transform-none",[["transform","none"]])}j("transform-flat",[["transform-style","flat"]]),j("transform-3d",[["transform-style","preserve-3d"]]),j("transform-content",[["transform-box","content-box"]]),j("transform-border",[["transform-box","border-box"]]),j("transform-fill",[["transform-box","fill-box"]]),j("transform-stroke",[["transform-box","stroke-box"]]),j("transform-view",[["transform-box","view-box"]]),j("backface-visible",[["backface-visibility","visible"]]),j("backface-hidden",[["backface-visibility","hidden"]]);for(let a of["auto","default","pointer","wait","text","move","help","not-allowed","none","context-menu","progress","cell","crosshair","vertical-text","alias","copy","no-drop","grab","grabbing","all-scroll","col-resize","row-resize","n-resize","e-resize","s-resize","w-resize","ne-resize","nw-resize","se-resize","sw-resize","ew-resize","ns-resize","nesw-resize","nwse-resize","zoom-in","zoom-out"])j(`cursor-${a}`,[["cursor",a]]);r("cursor",{themeKeys:["--cursor"],handle:a=>[o("cursor",a)]});for(let a of["auto","none","manipulation"])j(`touch-${a}`,[["touch-action",a]]);let u=()=>S([_("--tw-pan-x"),_("--tw-pan-y"),_("--tw-pinch-zoom")]);for(let a of["x","left","right"])j(`touch-pan-${a}`,[u,["--tw-pan-x",`pan-${a}`],["touch-action","var(--tw-pan-x,) var(--tw-pan-y,) var(--tw-pinch-zoom,)"]]);for(let a of["y","up","down"])j(`touch-pan-${a}`,[u,["--tw-pan-y",`pan-${a}`],["touch-action","var(--tw-pan-x,) var(--tw-pan-y,) var(--tw-pinch-zoom,)"]]);j("touch-pinch-zoom",[u,["--tw-pinch-zoom","pinch-zoom"],["touch-action","var(--tw-pan-x,) var(--tw-pan-y,) var(--tw-pinch-zoom,)"]]);for(let a of["none","text","all","auto"])j(`select-${a}`,[["-webkit-user-select",a],["user-select",a]]);j("resize-none",[["resize","none"]]),j("resize-x",[["resize","horizontal"]]),j("resize-y",[["resize","vertical"]]),j("resize",[["resize","both"]]),j("snap-none",[["scroll-snap-type","none"]]);let g=()=>S([_("--tw-scroll-snap-strictness","proximity","*")]);for(let a of["x","y","both"])j(`snap-${a}`,[g,["scroll-snap-type",`${a} var(--tw-scroll-snap-strictness)`]]);j("snap-mandatory",[g,["--tw-scroll-snap-strictness","mandatory"]]),j("snap-proximity",[g,["--tw-scroll-snap-strictness","proximity"]]),j("snap-align-none",[["scroll-snap-align","none"]]),j("snap-start",[["scroll-snap-align","start"]]),j("snap-end",[["scroll-snap-align","end"]]),j("snap-center",[["scroll-snap-align","center"]]),j("snap-normal",[["scroll-snap-stop","normal"]]),j("snap-always",[["scroll-snap-stop","always"]]);for(let[a,d]of[["scroll-m","scroll-margin"],["scroll-mx","scroll-margin-inline"],["scroll-my","scroll-margin-block"],["scroll-ms","scroll-margin-inline-start"],["scroll-me","scroll-margin-inline-end"],["scroll-mt","scroll-margin-top"],["scroll-mr","scroll-margin-right"],["scroll-mb","scroll-margin-bottom"],["scroll-ml","scroll-margin-left"]])i(a,["--scroll-margin","--spacing"],m=>[o(d,m)],{supportsNegative:!0});for(let[a,d]of[["scroll-p","scroll-padding"],["scroll-px","scroll-padding-inline"],["scroll-py","scroll-padding-block"],["scroll-ps","scroll-padding-inline-start"],["scroll-pe","scroll-padding-inline-end"],["scroll-pt","scroll-padding-top"],["scroll-pr","scroll-padding-right"],["scroll-pb","scroll-padding-bottom"],["scroll-pl","scroll-padding-left"]])i(a,["--scroll-padding","--spacing"],m=>[o(d,m)]);j("list-inside",[["list-style-position","inside"]]),j("list-outside",[["list-style-position","outside"]]),j("list-none",[["list-style-type","none"]]),j("list-disc",[["list-style-type","disc"]]),j("list-decimal",[["list-style-type","decimal"]]),r("list",{themeKeys:["--list-style-type"],handle:a=>[o("list-style-type",a)]}),j("list-image-none",[["list-style-image","none"]]),r("list-image",{themeKeys:["--list-style-image"],handle:a=>[o("list-style-image",a)]}),j("appearance-none",[["appearance","none"]]),j("appearance-auto",[["appearance","auto"]]),j("scheme-normal",[["color-scheme","normal"]]),j("scheme-dark",[["color-scheme","dark"]]),j("scheme-light",[["color-scheme","light"]]),j("scheme-light-dark",[["color-scheme","light dark"]]),j("scheme-only-dark",[["color-scheme","only dark"]]),j("scheme-only-light",[["color-scheme","only light"]]),j("columns-auto",[["columns","auto"]]),r("columns",{themeKeys:["--columns","--container"],handleBareValue:({value:a})=>F(a)?a:null,handle:a=>[o("columns",a)]}),t("columns",()=>[{values:Array.from({length:12},(a,d)=>`${d+1}`),valueThemeKeys:["--columns","--container"]}]);for(let a of["auto","avoid","all","avoid-page","page","left","right","column"])j(`break-before-${a}`,[["break-before",a]]);for(let a of["auto","avoid","avoid-page","avoid-column"])j(`break-inside-${a}`,[["break-inside",a]]);for(let a of["auto","avoid","all","avoid-page","page","left","right","column"])j(`break-after-${a}`,[["break-after",a]]);j("grid-flow-row",[["grid-auto-flow","row"]]),j("grid-flow-col",[["grid-auto-flow","column"]]),j("grid-flow-dense",[["grid-auto-flow","dense"]]),j("grid-flow-row-dense",[["grid-auto-flow","row dense"]]),j("grid-flow-col-dense",[["grid-auto-flow","column dense"]]),j("auto-cols-auto",[["grid-auto-columns","auto"]]),j("auto-cols-min",[["grid-auto-columns","min-content"]]),j("auto-cols-max",[["grid-auto-columns","max-content"]]),j("auto-cols-fr",[["grid-auto-columns","minmax(0, 1fr)"]]),r("auto-cols",{themeKeys:["--grid-auto-columns"],handle:a=>[o("grid-auto-columns",a)]}),j("auto-rows-auto",[["grid-auto-rows","auto"]]),j("auto-rows-min",[["grid-auto-rows","min-content"]]),j("auto-rows-max",[["grid-auto-rows","max-content"]]),j("auto-rows-fr",[["grid-auto-rows","minmax(0, 1fr)"]]),r("auto-rows",{themeKeys:["--grid-auto-rows"],handle:a=>[o("grid-auto-rows",a)]}),j("grid-cols-none",[["grid-template-columns","none"]]),j("grid-cols-subgrid",[["grid-template-columns","subgrid"]]),r("grid-cols",{themeKeys:["--grid-template-columns"],handleBareValue:({value:a})=>mj(a)?`repeat(${a}, minmax(0, 1fr))`:null,handle:a=>[o("grid-template-columns",a)]}),j("grid-rows-none",[["grid-template-rows","none"]]),j("grid-rows-subgrid",[["grid-template-rows","subgrid"]]),r("grid-rows",{themeKeys:["--grid-template-rows"],handleBareValue:({value:a})=>mj(a)?`repeat(${a}, minmax(0, 1fr))`:null,handle:a=>[o("grid-template-rows",a)]}),t("grid-cols",()=>[{values:Array.from({length:12},(a,d)=>`${d+1}`),valueThemeKeys:["--grid-template-columns"]}]),t("grid-rows",()=>[{values:Array.from({length:12},(a,d)=>`${d+1}`),valueThemeKeys:["--grid-template-rows"]}]),j("flex-row",[["flex-direction","row"]]),j("flex-row-reverse",[["flex-direction","row-reverse"]]),j("flex-col",[["flex-direction","column"]]),j("flex-col-reverse",[["flex-direction","column-reverse"]]),j("flex-wrap",[["flex-wrap","wrap"]]),j("flex-nowrap",[["flex-wrap","nowrap"]]),j("flex-wrap-reverse",[["flex-wrap","wrap-reverse"]]),j("place-content-center",[["place-content","center"]]),j("place-content-start",[["place-content","start"]]),j("place-content-end",[["place-content","end"]]),j("place-content-center-safe",[["place-content","safe center"]]),j("place-content-end-safe",[["place-content","safe end"]]),j("place-content-between",[["place-content","space-between"]]),j("place-content-around",[["place-content","space-around"]]),j("place-content-evenly",[["place-content","space-evenly"]]),j("place-content-baseline",[["place-content","baseline"]]),j("place-content-stretch",[["place-content","stretch"]]),j("place-items-center",[["place-items","center"]]),j("place-items-start",[["place-items","start"]]),j("place-items-end",[["place-items","end"]]),j("place-items-center-safe",[["place-items","safe center"]]),j("place-items-end-safe",[["place-items","safe end"]]),j("place-items-baseline",[["place-items","baseline"]]),j("place-items-stretch",[["place-items","stretch"]]),j("content-normal",[["align-content","normal"]]),j("content-center",[["align-content","center"]]),j("content-start",[["align-content","flex-start"]]),j("content-end",[["align-content","flex-end"]]),j("content-center-safe",[["align-content","safe center"]]),j("content-end-safe",[["align-content","safe flex-end"]]),j("content-between",[["align-content","space-between"]]),j("content-around",[["align-content","space-around"]]),j("content-evenly",[["align-content","space-evenly"]]),j("content-baseline",[["align-content","baseline"]]),j("content-stretch",[["align-content","stretch"]]),j("items-center",[["align-items","center"]]),j("items-start",[["align-items","flex-start"]]),j("items-end",[["align-items","flex-end"]]),j("items-center-safe",[["align-items","safe center"]]),j("items-end-safe",[["align-items","safe flex-end"]]),j("items-baseline",[["align-items","baseline"]]),j("items-baseline-last",[["align-items","last baseline"]]),j("items-stretch",[["align-items","stretch"]]),j("justify-normal",[["justify-content","normal"]]),j("justify-center",[["justify-content","center"]]),j("justify-start",[["justify-content","flex-start"]]),j("justify-end",[["justify-content","flex-end"]]),j("justify-center-safe",[["justify-content","safe center"]]),j("justify-end-safe",[["justify-content","safe flex-end"]]),j("justify-between",[["justify-content","space-between"]]),j("justify-around",[["justify-content","space-around"]]),j("justify-evenly",[["justify-content","space-evenly"]]),j("justify-baseline",[["justify-content","baseline"]]),j("justify-stretch",[["justify-content","stretch"]]),j("justify-items-normal",[["justify-items","normal"]]),j("justify-items-center",[["justify-items","center"]]),j("justify-items-start",[["justify-items","start"]]),j("justify-items-end",[["justify-items","end"]]),j("justify-items-center-safe",[["justify-items","safe center"]]),j("justify-items-end-safe",[["justify-items","safe end"]]),j("justify-items-stretch",[["justify-items","stretch"]]),i("gap",["--gap","--spacing"],a=>[o("gap",a)]),i("gap-x",["--gap","--spacing"],a=>[o("column-gap",a)]),i("gap-y",["--gap","--spacing"],a=>[o("row-gap",a)]),i("space-x",["--space","--spacing"],a=>[S([_("--tw-space-x-reverse","0")]),X(":where(& > :not(:last-child))",[o("--tw-sort","row-gap"),o("--tw-space-x-reverse","0"),o("margin-inline-start",`calc(${a} * var(--tw-space-x-reverse))`),o("margin-inline-end",`calc(${a} * calc(1 - var(--tw-space-x-reverse)))`)])],{supportsNegative:!0}),i("space-y",["--space","--spacing"],a=>[S([_("--tw-space-y-reverse","0")]),X(":where(& > :not(:last-child))",[o("--tw-sort","column-gap"),o("--tw-space-y-reverse","0"),o("margin-block-start",`calc(${a} * var(--tw-space-y-reverse))`),o("margin-block-end",`calc(${a} * calc(1 - var(--tw-space-y-reverse)))`)])],{supportsNegative:!0}),j("space-x-reverse",[()=>S([_("--tw-space-x-reverse","0")]),()=>X(":where(& > :not(:last-child))",[o("--tw-sort","row-gap"),o("--tw-space-x-reverse","1")])]),j("space-y-reverse",[()=>S([_("--tw-space-y-reverse","0")]),()=>X(":where(& > :not(:last-child))",[o("--tw-sort","column-gap"),o("--tw-space-y-reverse","1")])]),j("accent-auto",[["accent-color","auto"]]),n("accent",{themeKeys:["--accent-color","--color"],handle:a=>[o("accent-color",a)]}),n("caret",{themeKeys:["--caret-color","--color"],handle:a=>[o("caret-color",a)]}),n("divide",{themeKeys:["--divide-color","--border-color","--color"],handle:a=>[X(":where(& > :not(:last-child))",[o("--tw-sort","divide-color"),o("border-color",a)])]}),j("place-self-auto",[["place-self","auto"]]),j("place-self-start",[["place-self","start"]]),j("place-self-end",[["place-self","end"]]),j("place-self-center",[["place-self","center"]]),j("place-self-end-safe",[["place-self","safe end"]]),j("place-self-center-safe",[["place-self","safe center"]]),j("place-self-stretch",[["place-self","stretch"]]),j("self-auto",[["align-self","auto"]]),j("self-start",[["align-self","flex-start"]]),j("self-end",[["align-self","flex-end"]]),j("self-center",[["align-self","center"]]),j("self-end-safe",[["align-self","safe flex-end"]]),j("self-center-safe",[["align-self","safe center"]]),j("self-stretch",[["align-self","stretch"]]),j("self-baseline",[["align-self","baseline"]]),j("self-baseline-last",[["align-self","last baseline"]]),j("justify-self-auto",[["justify-self","auto"]]),j("justify-self-start",[["justify-self","flex-start"]]),j("justify-self-end",[["justify-self","flex-end"]]),j("justify-self-center",[["justify-self","center"]]),j("justify-self-end-safe",[["justify-self","safe flex-end"]]),j("justify-self-center-safe",[["justify-self","safe center"]]),j("justify-self-stretch",[["justify-self","stretch"]]);for(let a of["auto","hidden","clip","visible","scroll"])j(`overflow-${a}`,[["overflow",a]]),j(`overflow-x-${a}`,[["overflow-x",a]]),j(`overflow-y-${a}`,[["overflow-y",a]]);for(let a of["auto","contain","none"])j(`overscroll-${a}`,[["overscroll-behavior",a]]),j(`overscroll-x-${a}`,[["overscroll-behavior-x",a]]),j(`overscroll-y-${a}`,[["overscroll-behavior-y",a]]);j("scroll-auto",[["scroll-behavior","auto"]]),j("scroll-smooth",[["scroll-behavior","smooth"]]),j("truncate",[["overflow","hidden"],["text-overflow","ellipsis"],["white-space","nowrap"]]),j("text-ellipsis",[["text-overflow","ellipsis"]]),j("text-clip",[["text-overflow","clip"]]),j("hyphens-none",[["-webkit-hyphens","none"],["hyphens","none"]]),j("hyphens-manual",[["-webkit-hyphens","manual"],["hyphens","manual"]]),j("hyphens-auto",[["-webkit-hyphens","auto"],["hyphens","auto"]]),j("whitespace-normal",[["white-space","normal"]]),j("whitespace-nowrap",[["white-space","nowrap"]]),j("whitespace-pre",[["white-space","pre"]]),j("whitespace-pre-line",[["white-space","pre-line"]]),j("whitespace-pre-wrap",[["white-space","pre-wrap"]]),j("whitespace-break-spaces",[["white-space","break-spaces"]]),j("text-wrap",[["text-wrap","wrap"]]),j("text-nowrap",[["text-wrap","nowrap"]]),j("text-balance",[["text-wrap","balance"]]),j("text-pretty",[["text-wrap","pretty"]]),j("break-normal",[["overflow-wrap","normal"],["word-break","normal"]]),j("break-words",[["overflow-wrap","break-word"]]),j("break-all",[["word-break","break-all"]]),j("break-keep",[["word-break","keep-all"]]),j("wrap-anywhere",[["overflow-wrap","anywhere"]]),j("wrap-break-word",[["overflow-wrap","break-word"]]),j("wrap-normal",[["overflow-wrap","normal"]]);for(let[a,d]of[["rounded",["border-radius"]],["rounded-s",["border-start-start-radius","border-end-start-radius"]],["rounded-e",["border-start-end-radius","border-end-end-radius"]],["rounded-t",["border-top-left-radius","border-top-right-radius"]],["rounded-r",["border-top-right-radius","border-bottom-right-radius"]],["rounded-b",["border-bottom-right-radius","border-bottom-left-radius"]],["rounded-l",["border-top-left-radius","border-bottom-left-radius"]],["rounded-ss",["border-start-start-radius"]],["rounded-se",["border-start-end-radius"]],["rounded-ee",["border-end-end-radius"]],["rounded-es",["border-end-start-radius"]],["rounded-tl",["border-top-left-radius"]],["rounded-tr",["border-top-right-radius"]],["rounded-br",["border-bottom-right-radius"]],["rounded-bl",["border-bottom-left-radius"]]])j(`${a}-none`,d.map(m=>[m,"0"])),j(`${a}-full`,d.map(m=>[m,"calc(infinity * 1px)"])),r(a,{themeKeys:["--radius"],handle:m=>d.map(B=>o(B,m))});j("border-solid",[["--tw-border-style","solid"],["border-style","solid"]]),j("border-dashed",[["--tw-border-style","dashed"],["border-style","dashed"]]),j("border-dotted",[["--tw-border-style","dotted"],["border-style","dotted"]]),j("border-double",[["--tw-border-style","double"],["border-style","double"]]),j("border-hidden",[["--tw-border-style","hidden"],["border-style","hidden"]]),j("border-none",[["--tw-border-style","none"],["border-style","none"]]);{let a=function(m,B){e.functional(m,k=>{if(!k.value){if(k.modifier)return;let v=A.get(["--default-border-width"])??"1px",y=B.width(v);return y?[d(),...y]:void 0}if(k.value.kind==="arbitrary"){let v=k.value.value;switch(k.value.dataType??V(v,["color","line-width","length"])){case"line-width":case"length":{if(k.modifier)return;let y=B.width(v);return y?[d(),...y]:void 0}default:return v=Y(v,k.modifier,A),v===null?void 0:B.color(v)}}{let v=Q(k,A,["--border-color","--color"]);if(v)return B.color(v)}{if(k.modifier)return;let v=A.resolve(k.value.value,["--border-width"]);if(v){let y=B.width(v);return y?[d(),...y]:void 0}if(F(k.value.value)){let y=B.width(`${k.value.value}px`);return y?[d(),...y]:void 0}}}),t(m,()=>[{values:["current","inherit","transparent"],valueThemeKeys:["--border-color","--color"],modifiers:Array.from({length:21},(k,v)=>`${v*5}`),hasDefaultValue:!0},{values:["0","2","4","8"],valueThemeKeys:["--border-width"]}])};var p=a;let d=()=>S([_("--tw-border-style","solid")]);a("border",{width:m=>[o("border-style","var(--tw-border-style)"),o("border-width",m)],color:m=>[o("border-color",m)]}),a("border-x",{width:m=>[o("border-inline-style","var(--tw-border-style)"),o("border-inline-width",m)],color:m=>[o("border-inline-color",m)]}),a("border-y",{width:m=>[o("border-block-style","var(--tw-border-style)"),o("border-block-width",m)],color:m=>[o("border-block-color",m)]}),a("border-s",{width:m=>[o("border-inline-start-style","var(--tw-border-style)"),o("border-inline-start-width",m)],color:m=>[o("border-inline-start-color",m)]}),a("border-e",{width:m=>[o("border-inline-end-style","var(--tw-border-style)"),o("border-inline-end-width",m)],color:m=>[o("border-inline-end-color",m)]}),a("border-t",{width:m=>[o("border-top-style","var(--tw-border-style)"),o("border-top-width",m)],color:m=>[o("border-top-color",m)]}),a("border-r",{width:m=>[o("border-right-style","var(--tw-border-style)"),o("border-right-width",m)],color:m=>[o("border-right-color",m)]}),a("border-b",{width:m=>[o("border-bottom-style","var(--tw-border-style)"),o("border-bottom-width",m)],color:m=>[o("border-bottom-color",m)]}),a("border-l",{width:m=>[o("border-left-style","var(--tw-border-style)"),o("border-left-width",m)],color:m=>[o("border-left-color",m)]}),r("divide-x",{defaultValue:A.get(["--default-border-width"])??"1px",themeKeys:["--divide-width","--border-width"],handleBareValue:({value:m})=>F(m)?`${m}px`:null,handle:m=>[S([_("--tw-divide-x-reverse","0")]),X(":where(& > :not(:last-child))",[o("--tw-sort","divide-x-width"),d(),o("--tw-divide-x-reverse","0"),o("border-inline-style","var(--tw-border-style)"),o("border-inline-start-width",`calc(${m} * var(--tw-divide-x-reverse))`),o("border-inline-end-width",`calc(${m} * calc(1 - var(--tw-divide-x-reverse)))`)])]}),r("divide-y",{defaultValue:A.get(["--default-border-width"])??"1px",themeKeys:["--divide-width","--border-width"],handleBareValue:({value:m})=>F(m)?`${m}px`:null,handle:m=>[S([_("--tw-divide-y-reverse","0")]),X(":where(& > :not(:last-child))",[o("--tw-sort","divide-y-width"),d(),o("--tw-divide-y-reverse","0"),o("border-bottom-style","var(--tw-border-style)"),o("border-top-style","var(--tw-border-style)"),o("border-top-width",`calc(${m} * var(--tw-divide-y-reverse))`),o("border-bottom-width",`calc(${m} * calc(1 - var(--tw-divide-y-reverse)))`)])]}),t("divide-x",()=>[{values:["0","2","4","8"],valueThemeKeys:["--divide-width","--border-width"],hasDefaultValue:!0}]),t("divide-y",()=>[{values:["0","2","4","8"],valueThemeKeys:["--divide-width","--border-width"],hasDefaultValue:!0}]),j("divide-x-reverse",[()=>S([_("--tw-divide-x-reverse","0")]),()=>X(":where(& > :not(:last-child))",[o("--tw-divide-x-reverse","1")])]),j("divide-y-reverse",[()=>S([_("--tw-divide-y-reverse","0")]),()=>X(":where(& > :not(:last-child))",[o("--tw-divide-y-reverse","1")])]);for(let m of["solid","dashed","dotted","double","none"])j(`divide-${m}`,[()=>X(":where(& > :not(:last-child))",[o("--tw-sort","divide-style"),o("--tw-border-style",m),o("border-style",m)])])}j("bg-auto",[["background-size","auto"]]),j("bg-cover",[["background-size","cover"]]),j("bg-contain",[["background-size","contain"]]),r("bg-size",{handle(a){if(a)return[o("background-size",a)]}}),j("bg-fixed",[["background-attachment","fixed"]]),j("bg-local",[["background-attachment","local"]]),j("bg-scroll",[["background-attachment","scroll"]]),j("bg-top",[["background-position","top"]]),j("bg-top-left",[["background-position","left top"]]),j("bg-top-right",[["background-position","right top"]]),j("bg-bottom",[["background-position","bottom"]]),j("bg-bottom-left",[["background-position","left bottom"]]),j("bg-bottom-right",[["background-position","right bottom"]]),j("bg-left",[["background-position","left"]]),j("bg-right",[["background-position","right"]]),j("bg-center",[["background-position","center"]]),r("bg-position",{handle(a){if(a)return[o("background-position",a)]}}),j("bg-repeat",[["background-repeat","repeat"]]),j("bg-no-repeat",[["background-repeat","no-repeat"]]),j("bg-repeat-x",[["background-repeat","repeat-x"]]),j("bg-repeat-y",[["background-repeat","repeat-y"]]),j("bg-repeat-round",[["background-repeat","round"]]),j("bg-repeat-space",[["background-repeat","space"]]),j("bg-none",[["background-image","none"]]);{let a=function(v){let y="in oklab";if(v?.kind==="named")switch(v.value){case"longer":case"shorter":case"increasing":case"decreasing":y=`in oklch ${v.value} hue`;break;default:y=`in ${v.value}`}else v?.kind==="arbitrary"&&(y=v.value);return y},d=function({negative:v}){return y=>{if(!y.value)return;if(y.value.kind==="arbitrary"){if(y.modifier)return;let T=y.value.value;switch(y.value.dataType??V(T,["angle"])){case"angle":return T=v?`calc(${T} * -1)`:`${T}`,[o("--tw-gradient-position",T),o("background-image",`linear-gradient(var(--tw-gradient-stops,${T}))`)];default:return v?void 0:[o("--tw-gradient-position",T),o("background-image",`linear-gradient(var(--tw-gradient-stops,${T}))`)]}}let $=y.value.value;if(!v&&k.has($))$=k.get($);else if(F($))$=v?`calc(${$}deg * -1)`:`${$}deg`;else return;let q=a(y.modifier);return[o("--tw-gradient-position",`${$}`),Z("@supports (background-image: linear-gradient(in lab, red, red))",[o("--tw-gradient-position",`${$} ${q}`)]),o("background-image","linear-gradient(var(--tw-gradient-stops))")]}},m=function({negative:v}){return y=>{if(y.value?.kind==="arbitrary"){if(y.modifier)return;let T=y.value.value;return[o("--tw-gradient-position",T),o("background-image",`conic-gradient(var(--tw-gradient-stops,${T}))`)]}let $=a(y.modifier);if(!y.value)return[o("--tw-gradient-position",$),o("background-image","conic-gradient(var(--tw-gradient-stops))")];let q=y.value.value;if(F(q))return q=v?`calc(${q}deg * -1)`:`${q}deg`,[o("--tw-gradient-position",`from ${q} ${$}`),o("background-image","conic-gradient(var(--tw-gradient-stops))")]}};var E=a,x=d,I=m;let B=["oklab","oklch","srgb","hsl","longer","shorter","increasing","decreasing"],k=new Map([["to-t","to top"],["to-tr","to top right"],["to-r","to right"],["to-br","to bottom right"],["to-b","to bottom"],["to-bl","to bottom left"],["to-l","to left"],["to-tl","to top left"]]);e.functional("-bg-linear",d({negative:!0})),e.functional("bg-linear",d({negative:!1})),t("bg-linear",()=>[{values:[...k.keys()],modifiers:B},{values:["0","30","60","90","120","150","180","210","240","270","300","330"],supportsNegative:!0,modifiers:B}]),e.functional("-bg-conic",m({negative:!0})),e.functional("bg-conic",m({negative:!1})),t("bg-conic",()=>[{hasDefaultValue:!0,modifiers:B},{values:["0","30","60","90","120","150","180","210","240","270","300","330"],supportsNegative:!0,modifiers:B}]),e.functional("bg-radial",v=>{if(!v.value){let y=a(v.modifier);return[o("--tw-gradient-position",y),o("background-image","radial-gradient(var(--tw-gradient-stops))")]}if(v.value.kind==="arbitrary"){if(v.modifier)return;let y=v.value.value;return[o("--tw-gradient-position",y),o("background-image",`radial-gradient(var(--tw-gradient-stops,${y}))`)]}}),t("bg-radial",()=>[{hasDefaultValue:!0,modifiers:B}])}e.functional("bg",a=>{if(a.value){if(a.value.kind==="arbitrary"){let d=a.value.value;switch(a.value.dataType??V(d,["image","color","percentage","position","bg-size","length","url"])){case"percentage":case"position":return a.modifier?void 0:[o("background-position",d)];case"bg-size":case"length":case"size":return a.modifier?void 0:[o("background-size",d)];case"image":case"url":return a.modifier?void 0:[o("background-image",d)];default:return d=Y(d,a.modifier,A),d===null?void 0:[o("background-color",d)]}}{let d=Q(a,A,["--background-color","--color"]);if(d)return[o("background-color",d)]}{if(a.modifier)return;let d=A.resolve(a.value.value,["--background-image"]);if(d)return[o("background-image",d)]}}}),t("bg",()=>[{values:["current","inherit","transparent"],valueThemeKeys:["--background-color","--color"],modifiers:Array.from({length:21},(a,d)=>`${d*5}`)},{values:[],valueThemeKeys:["--background-image"]}]);let b=()=>S([_("--tw-gradient-position"),_("--tw-gradient-from","#0000","<color>"),_("--tw-gradient-via","#0000","<color>"),_("--tw-gradient-to","#0000","<color>"),_("--tw-gradient-stops"),_("--tw-gradient-via-stops"),_("--tw-gradient-from-position","0%","<length-percentage>"),_("--tw-gradient-via-position","50%","<length-percentage>"),_("--tw-gradient-to-position","100%","<length-percentage>")]);function G(a,d){e.functional(a,m=>{if(m.value){if(m.value.kind==="arbitrary"){let B=m.value.value;switch(m.value.dataType??V(B,["color","length","percentage"])){case"length":case"percentage":return m.modifier?void 0:d.position(B);default:return B=Y(B,m.modifier,A),B===null?void 0:d.color(B)}}{let B=Q(m,A,["--background-color","--color"]);if(B)return d.color(B)}{if(m.modifier)return;let B=A.resolve(m.value.value,["--gradient-color-stop-positions"]);if(B)return d.position(B);if(m.value.value[m.value.value.length-1]==="%"&&F(m.value.value.slice(0,-1)))return d.position(m.value.value)}}}),t(a,()=>[{values:["current","inherit","transparent"],valueThemeKeys:["--background-color","--color"],modifiers:Array.from({length:21},(m,B)=>`${B*5}`)},{values:Array.from({length:21},(m,B)=>`${B*5}%`),valueThemeKeys:["--gradient-color-stop-positions"]}])}G("from",{color:a=>[b(),o("--tw-sort","--tw-gradient-from"),o("--tw-gradient-from",a),o("--tw-gradient-stops","var(--tw-gradient-via-stops, var(--tw-gradient-position), var(--tw-gradient-from) var(--tw-gradient-from-position), var(--tw-gradient-to) var(--tw-gradient-to-position))")],position:a=>[b(),o("--tw-gradient-from-position",a)]}),j("via-none",[["--tw-gradient-via-stops","initial"]]),G("via",{color:a=>[b(),o("--tw-sort","--tw-gradient-via"),o("--tw-gradient-via",a),o("--tw-gradient-via-stops","var(--tw-gradient-position), var(--tw-gradient-from) var(--tw-gradient-from-position), var(--tw-gradient-via) var(--tw-gradient-via-position), var(--tw-gradient-to) var(--tw-gradient-to-position)"),o("--tw-gradient-stops","var(--tw-gradient-via-stops)")],position:a=>[b(),o("--tw-gradient-via-position",a)]}),G("to",{color:a=>[b(),o("--tw-sort","--tw-gradient-to"),o("--tw-gradient-to",a),o("--tw-gradient-stops","var(--tw-gradient-via-stops, var(--tw-gradient-position), var(--tw-gradient-from) var(--tw-gradient-from-position), var(--tw-gradient-to) var(--tw-gradient-to-position))")],position:a=>[b(),o("--tw-gradient-to-position",a)]}),j("mask-none",[["mask-image","none"]]),e.functional("mask",a=>{if(!a.value||a.modifier||a.value.kind!=="arbitrary")return;let d=a.value.value;switch(a.value.dataType??V(d,["image","percentage","position","bg-size","length","url"])){case"percentage":case"position":return a.modifier?void 0:[o("mask-position",d)];case"bg-size":case"length":case"size":return[o("mask-size",d)];case"image":case"url":default:return[o("mask-image",d)]}}),j("mask-add",[["mask-composite","add"]]),j("mask-subtract",[["mask-composite","subtract"]]),j("mask-intersect",[["mask-composite","intersect"]]),j("mask-exclude",[["mask-composite","exclude"]]),j("mask-alpha",[["mask-mode","alpha"]]),j("mask-luminance",[["mask-mode","luminance"]]),j("mask-match",[["mask-mode","match-source"]]),j("mask-type-alpha",[["mask-type","alpha"]]),j("mask-type-luminance",[["mask-type","luminance"]]),j("mask-auto",[["mask-size","auto"]]),j("mask-cover",[["mask-size","cover"]]),j("mask-contain",[["mask-size","contain"]]),r("mask-size",{handle(a){if(a)return[o("mask-size",a)]}}),j("mask-top",[["mask-position","top"]]),j("mask-top-left",[["mask-position","left top"]]),j("mask-top-right",[["mask-position","right top"]]),j("mask-bottom",[["mask-position","bottom"]]),j("mask-bottom-left",[["mask-position","left bottom"]]),j("mask-bottom-right",[["mask-position","right bottom"]]),j("mask-left",[["mask-position","left"]]),j("mask-right",[["mask-position","right"]]),j("mask-center",[["mask-position","center"]]),r("mask-position",{handle(a){if(a)return[o("mask-position",a)]}}),j("mask-repeat",[["mask-repeat","repeat"]]),j("mask-no-repeat",[["mask-repeat","no-repeat"]]),j("mask-repeat-x",[["mask-repeat","repeat-x"]]),j("mask-repeat-y",[["mask-repeat","repeat-y"]]),j("mask-repeat-round",[["mask-repeat","round"]]),j("mask-repeat-space",[["mask-repeat","space"]]),j("mask-clip-border",[["mask-clip","border-box"]]),j("mask-clip-padding",[["mask-clip","padding-box"]]),j("mask-clip-content",[["mask-clip","content-box"]]),j("mask-clip-fill",[["mask-clip","fill-box"]]),j("mask-clip-stroke",[["mask-clip","stroke-box"]]),j("mask-clip-view",[["mask-clip","view-box"]]),j("mask-no-clip",[["mask-clip","no-clip"]]),j("mask-origin-border",[["mask-origin","border-box"]]),j("mask-origin-padding",[["mask-origin","padding-box"]]),j("mask-origin-content",[["mask-origin","content-box"]]),j("mask-origin-fill",[["mask-origin","fill-box"]]),j("mask-origin-stroke",[["mask-origin","stroke-box"]]),j("mask-origin-view",[["mask-origin","view-box"]]);let w=()=>S([_("--tw-mask-linear","linear-gradient(#fff, #fff)"),_("--tw-mask-radial","linear-gradient(#fff, #fff)"),_("--tw-mask-conic","linear-gradient(#fff, #fff)")]);function P(a,d){e.functional(a,m=>{if(m.value){if(m.value.kind==="arbitrary"){let B=m.value.value;switch(m.value.dataType??V(B,["length","percentage","color"])){case"color":return B=Y(B,m.modifier,A),B===null?void 0:d.color(B);case"percentage":return m.modifier||!F(B.slice(0,-1))?void 0:d.position(B);default:return m.modifier?void 0:d.position(B)}}{let B=Q(m,A,["--background-color","--color"]);if(B)return d.color(B)}{if(m.modifier)return;let B=V(m.value.value,["number","percentage"]);if(!B)return;switch(B){case"number":{let k=A.resolve(null,["--spacing"]);return!k||!pA(m.value.value)?void 0:d.position(`calc(${k} * ${m.value.value})`)}case"percentage":return F(m.value.value.slice(0,-1))?d.position(m.value.value):void 0;default:return}}}}),t(a,()=>[{values:["current","inherit","transparent"],valueThemeKeys:["--background-color","--color"],modifiers:Array.from({length:21},(m,B)=>`${B*5}`)},{values:Array.from({length:21},(m,B)=>`${B*5}%`),valueThemeKeys:["--gradient-color-stop-positions"]}]),t(a,()=>[{values:Array.from({length:21},(m,B)=>`${B*5}%`)},{values:A.get(["--spacing"])?tj:[]},{values:["current","inherit","transparent"],valueThemeKeys:["--background-color","--color"],modifiers:Array.from({length:21},(m,B)=>`${B*5}`)}])}let K=()=>S([_("--tw-mask-left","linear-gradient(#fff, #fff)"),_("--tw-mask-right","linear-gradient(#fff, #fff)"),_("--tw-mask-bottom","linear-gradient(#fff, #fff)"),_("--tw-mask-top","linear-gradient(#fff, #fff)")]);function D(a,d,m){P(a,{color(B){let k=[w(),K(),o("mask-image","var(--tw-mask-linear), var(--tw-mask-radial), var(--tw-mask-conic)"),o("mask-composite","intersect"),o("--tw-mask-linear","var(--tw-mask-left), var(--tw-mask-right), var(--tw-mask-bottom), var(--tw-mask-top)")];for(let v of["top","right","bottom","left"])m[v]&&(k.push(o(`--tw-mask-${v}`,`linear-gradient(to ${v}, var(--tw-mask-${v}-from-color) var(--tw-mask-${v}-from-position), var(--tw-mask-${v}-to-color) var(--tw-mask-${v}-to-position))`)),k.push(S([_(`--tw-mask-${v}-from-position`,"0%"),_(`--tw-mask-${v}-to-position`,"100%"),_(`--tw-mask-${v}-from-color`,"black"),_(`--tw-mask-${v}-to-color`,"transparent")])),k.push(o(`--tw-mask-${v}-${d}-color`,B)));return k},position(B){let k=[w(),K(),o("mask-image","var(--tw-mask-linear), var(--tw-mask-radial), var(--tw-mask-conic)"),o("mask-composite","intersect"),o("--tw-mask-linear","var(--tw-mask-left), var(--tw-mask-right), var(--tw-mask-bottom), var(--tw-mask-top)")];for(let v of["top","right","bottom","left"])m[v]&&(k.push(o(`--tw-mask-${v}`,`linear-gradient(to ${v}, var(--tw-mask-${v}-from-color) var(--tw-mask-${v}-from-position), var(--tw-mask-${v}-to-color) var(--tw-mask-${v}-to-position))`)),k.push(S([_(`--tw-mask-${v}-from-position`,"0%"),_(`--tw-mask-${v}-to-position`,"100%"),_(`--tw-mask-${v}-from-color`,"black"),_(`--tw-mask-${v}-to-color`,"transparent")])),k.push(o(`--tw-mask-${v}-${d}-position`,B)));return k}})}D("mask-x-from","from",{top:!1,right:!0,bottom:!1,left:!0}),D("mask-x-to","to",{top:!1,right:!0,bottom:!1,left:!0}),D("mask-y-from","from",{top:!0,right:!1,bottom:!0,left:!1}),D("mask-y-to","to",{top:!0,right:!1,bottom:!0,left:!1}),D("mask-t-from","from",{top:!0,right:!1,bottom:!1,left:!1}),D("mask-t-to","to",{top:!0,right:!1,bottom:!1,left:!1}),D("mask-r-from","from",{top:!1,right:!0,bottom:!1,left:!1}),D("mask-r-to","to",{top:!1,right:!0,bottom:!1,left:!1}),D("mask-b-from","from",{top:!1,right:!1,bottom:!0,left:!1}),D("mask-b-to","to",{top:!1,right:!1,bottom:!0,left:!1}),D("mask-l-from","from",{top:!1,right:!1,bottom:!1,left:!0}),D("mask-l-to","to",{top:!1,right:!1,bottom:!1,left:!0});let O=()=>S([_("--tw-mask-linear-position","0deg"),_("--tw-mask-linear-from-position","0%"),_("--tw-mask-linear-to-position","100%"),_("--tw-mask-linear-from-color","black"),_("--tw-mask-linear-to-color","transparent")]);r("mask-linear",{defaultValue:null,supportsNegative:!0,supportsFractions:!1,handleBareValue(a){return F(a.value)?`calc(1deg * ${a.value})`:null},handleNegativeBareValue(a){return F(a.value)?`calc(1deg * -${a.value})`:null},handle:a=>[w(),O(),o("mask-image","var(--tw-mask-linear), var(--tw-mask-radial), var(--tw-mask-conic)"),o("mask-composite","intersect"),o("--tw-mask-linear","linear-gradient(var(--tw-mask-linear-stops, var(--tw-mask-linear-position)))"),o("--tw-mask-linear-position",a)]}),t("mask-linear",()=>[{supportsNegative:!0,values:["0","1","2","3","6","12","45","90","180"]}]),P("mask-linear-from",{color:a=>[w(),O(),o("mask-image","var(--tw-mask-linear), var(--tw-mask-radial), var(--tw-mask-conic)"),o("mask-composite","intersect"),o("--tw-mask-linear-stops","var(--tw-mask-linear-position), var(--tw-mask-linear-from-color) var(--tw-mask-linear-from-position), var(--tw-mask-linear-to-color) var(--tw-mask-linear-to-position)"),o("--tw-mask-linear","linear-gradient(var(--tw-mask-linear-stops))"),o("--tw-mask-linear-from-color",a)],position:a=>[w(),O(),o("mask-image","var(--tw-mask-linear), var(--tw-mask-radial), var(--tw-mask-conic)"),o("mask-composite","intersect"),o("--tw-mask-linear-stops","var(--tw-mask-linear-position), var(--tw-mask-linear-from-color) var(--tw-mask-linear-from-position), var(--tw-mask-linear-to-color) var(--tw-mask-linear-to-position)"),o("--tw-mask-linear","linear-gradient(var(--tw-mask-linear-stops))"),o("--tw-mask-linear-from-position",a)]}),P("mask-linear-to",{color:a=>[w(),O(),o("mask-image","var(--tw-mask-linear), var(--tw-mask-radial), var(--tw-mask-conic)"),o("mask-composite","intersect"),o("--tw-mask-linear-stops","var(--tw-mask-linear-position), var(--tw-mask-linear-from-color) var(--tw-mask-linear-from-position), var(--tw-mask-linear-to-color) var(--tw-mask-linear-to-position)"),o("--tw-mask-linear","linear-gradient(var(--tw-mask-linear-stops))"),o("--tw-mask-linear-to-color",a)],position:a=>[w(),O(),o("mask-image","var(--tw-mask-linear), var(--tw-mask-radial), var(--tw-mask-conic)"),o("mask-composite","intersect"),o("--tw-mask-linear-stops","var(--tw-mask-linear-position), var(--tw-mask-linear-from-color) var(--tw-mask-linear-from-position), var(--tw-mask-linear-to-color) var(--tw-mask-linear-to-position)"),o("--tw-mask-linear","linear-gradient(var(--tw-mask-linear-stops))"),o("--tw-mask-linear-to-position",a)]});let N=()=>S([_("--tw-mask-radial-from-position","0%"),_("--tw-mask-radial-to-position","100%"),_("--tw-mask-radial-from-color","black"),_("--tw-mask-radial-to-color","transparent"),_("--tw-mask-radial-shape","ellipse"),_("--tw-mask-radial-size","farthest-corner"),_("--tw-mask-radial-position","center")]);j("mask-circle",[["--tw-mask-radial-shape","circle"]]),j("mask-ellipse",[["--tw-mask-radial-shape","ellipse"]]),j("mask-radial-closest-side",[["--tw-mask-radial-size","closest-side"]]),j("mask-radial-farthest-side",[["--tw-mask-radial-size","farthest-side"]]),j("mask-radial-closest-corner",[["--tw-mask-radial-size","closest-corner"]]),j("mask-radial-farthest-corner",[["--tw-mask-radial-size","farthest-corner"]]),j("mask-radial-at-top",[["--tw-mask-radial-position","top"]]),j("mask-radial-at-top-left",[["--tw-mask-radial-position","top left"]]),j("mask-radial-at-top-right",[["--tw-mask-radial-position","top right"]]),j("mask-radial-at-bottom",[["--tw-mask-radial-position","bottom"]]),j("mask-radial-at-bottom-left",[["--tw-mask-radial-position","bottom left"]]),j("mask-radial-at-bottom-right",[["--tw-mask-radial-position","bottom right"]]),j("mask-radial-at-left",[["--tw-mask-radial-position","left"]]),j("mask-radial-at-right",[["--tw-mask-radial-position","right"]]),j("mask-radial-at-center",[["--tw-mask-radial-position","center"]]),r("mask-radial-at",{defaultValue:null,supportsNegative:!1,supportsFractions:!1,handle:a=>[o("--tw-mask-radial-position",a)]}),r("mask-radial",{defaultValue:null,supportsNegative:!1,supportsFractions:!1,handle:a=>[w(),N(),o("mask-image","var(--tw-mask-linear), var(--tw-mask-radial), var(--tw-mask-conic)"),o("mask-composite","intersect"),o("--tw-mask-radial","radial-gradient(var(--tw-mask-radial-stops, var(--tw-mask-radial-size)))"),o("--tw-mask-radial-size",a)]}),P("mask-radial-from",{color:a=>[w(),N(),o("mask-image","var(--tw-mask-linear), var(--tw-mask-radial), var(--tw-mask-conic)"),o("mask-composite","intersect"),o("--tw-mask-radial-stops","var(--tw-mask-radial-shape) var(--tw-mask-radial-size) at var(--tw-mask-radial-position), var(--tw-mask-radial-from-color) var(--tw-mask-radial-from-position), var(--tw-mask-radial-to-color) var(--tw-mask-radial-to-position)"),o("--tw-mask-radial","radial-gradient(var(--tw-mask-radial-stops))"),o("--tw-mask-radial-from-color",a)],position:a=>[w(),N(),o("mask-image","var(--tw-mask-linear), var(--tw-mask-radial), var(--tw-mask-conic)"),o("mask-composite","intersect"),o("--tw-mask-radial-stops","var(--tw-mask-radial-shape) var(--tw-mask-radial-size) at var(--tw-mask-radial-position), var(--tw-mask-radial-from-color) var(--tw-mask-radial-from-position), var(--tw-mask-radial-to-color) var(--tw-mask-radial-to-position)"),o("--tw-mask-radial","radial-gradient(var(--tw-mask-radial-stops))"),o("--tw-mask-radial-from-position",a)]}),P("mask-radial-to",{color:a=>[w(),N(),o("mask-image","var(--tw-mask-linear), var(--tw-mask-radial), var(--tw-mask-conic)"),o("mask-composite","intersect"),o("--tw-mask-radial-stops","var(--tw-mask-radial-shape) var(--tw-mask-radial-size) at var(--tw-mask-radial-position), var(--tw-mask-radial-from-color) var(--tw-mask-radial-from-position), var(--tw-mask-radial-to-color) var(--tw-mask-radial-to-position)"),o("--tw-mask-radial","radial-gradient(var(--tw-mask-radial-stops))"),o("--tw-mask-radial-to-color",a)],position:a=>[w(),N(),o("mask-image","var(--tw-mask-linear), var(--tw-mask-radial), var(--tw-mask-conic)"),o("mask-composite","intersect"),o("--tw-mask-radial-stops","var(--tw-mask-radial-shape) var(--tw-mask-radial-size) at var(--tw-mask-radial-position), var(--tw-mask-radial-from-color) var(--tw-mask-radial-from-position), var(--tw-mask-radial-to-color) var(--tw-mask-radial-to-position)"),o("--tw-mask-radial","radial-gradient(var(--tw-mask-radial-stops))"),o("--tw-mask-radial-to-position",a)]});let M=()=>S([_("--tw-mask-conic-position","0deg"),_("--tw-mask-conic-from-position","0%"),_("--tw-mask-conic-to-position","100%"),_("--tw-mask-conic-from-color","black"),_("--tw-mask-conic-to-color","transparent")]);r("mask-conic",{defaultValue:null,supportsNegative:!0,supportsFractions:!1,handleBareValue(a){return F(a.value)?`calc(1deg * ${a.value})`:null},handleNegativeBareValue(a){return F(a.value)?`calc(1deg * -${a.value})`:null},handle:a=>[w(),M(),o("mask-image","var(--tw-mask-linear), var(--tw-mask-radial), var(--tw-mask-conic)"),o("mask-composite","intersect"),o("--tw-mask-conic","conic-gradient(var(--tw-mask-conic-stops, var(--tw-mask-conic-position)))"),o("--tw-mask-conic-position",a)]}),t("mask-conic",()=>[{supportsNegative:!0,values:["0","1","2","3","6","12","45","90","180"]}]),P("mask-conic-from",{color:a=>[w(),M(),o("mask-image","var(--tw-mask-linear), var(--tw-mask-radial), var(--tw-mask-conic)"),o("mask-composite","intersect"),o("--tw-mask-conic-stops","from var(--tw-mask-conic-position), var(--tw-mask-conic-from-color) var(--tw-mask-conic-from-position), var(--tw-mask-conic-to-color) var(--tw-mask-conic-to-position)"),o("--tw-mask-conic","conic-gradient(var(--tw-mask-conic-stops))"),o("--tw-mask-conic-from-color",a)],position:a=>[w(),M(),o("mask-image","var(--tw-mask-linear), var(--tw-mask-radial), var(--tw-mask-conic)"),o("mask-composite","intersect"),o("--tw-mask-conic-stops","from var(--tw-mask-conic-position), var(--tw-mask-conic-from-color) var(--tw-mask-conic-from-position), var(--tw-mask-conic-to-color) var(--tw-mask-conic-to-position)"),o("--tw-mask-conic","conic-gradient(var(--tw-mask-conic-stops))"),o("--tw-mask-conic-from-position",a)]}),P("mask-conic-to",{color:a=>[w(),M(),o("mask-image","var(--tw-mask-linear), var(--tw-mask-radial), var(--tw-mask-conic)"),o("mask-composite","intersect"),o("--tw-mask-conic-stops","from var(--tw-mask-conic-position), var(--tw-mask-conic-from-color) var(--tw-mask-conic-from-position), var(--tw-mask-conic-to-color) var(--tw-mask-conic-to-position)"),o("--tw-mask-conic","conic-gradient(var(--tw-mask-conic-stops))"),o("--tw-mask-conic-to-color",a)],position:a=>[w(),M(),o("mask-image","var(--tw-mask-linear), var(--tw-mask-radial), var(--tw-mask-conic)"),o("mask-composite","intersect"),o("--tw-mask-conic-stops","from var(--tw-mask-conic-position), var(--tw-mask-conic-from-color) var(--tw-mask-conic-from-position), var(--tw-mask-conic-to-color) var(--tw-mask-conic-to-position)"),o("--tw-mask-conic","conic-gradient(var(--tw-mask-conic-stops))"),o("--tw-mask-conic-to-position",a)]}),j("box-decoration-slice",[["-webkit-box-decoration-break","slice"],["box-decoration-break","slice"]]),j("box-decoration-clone",[["-webkit-box-decoration-break","clone"],["box-decoration-break","clone"]]),j("bg-clip-text",[["background-clip","text"]]),j("bg-clip-border",[["background-clip","border-box"]]),j("bg-clip-padding",[["background-clip","padding-box"]]),j("bg-clip-content",[["background-clip","content-box"]]),j("bg-origin-border",[["background-origin","border-box"]]),j("bg-origin-padding",[["background-origin","padding-box"]]),j("bg-origin-content",[["background-origin","content-box"]]);for(let a of["normal","multiply","screen","overlay","darken","lighten","color-dodge","color-burn","hard-light","soft-light","difference","exclusion","hue","saturation","color","luminosity"])j(`bg-blend-${a}`,[["background-blend-mode",a]]),j(`mix-blend-${a}`,[["mix-blend-mode",a]]);j("mix-blend-plus-darker",[["mix-blend-mode","plus-darker"]]),j("mix-blend-plus-lighter",[["mix-blend-mode","plus-lighter"]]),j("fill-none",[["fill","none"]]),e.functional("fill",a=>{if(!a.value)return;if(a.value.kind==="arbitrary"){let m=Y(a.value.value,a.modifier,A);return m===null?void 0:[o("fill",m)]}let d=Q(a,A,["--fill","--color"]);if(d)return[o("fill",d)]}),t("fill",()=>[{values:["current","inherit","transparent"],valueThemeKeys:["--fill","--color"],modifiers:Array.from({length:21},(a,d)=>`${d*5}`)}]),j("stroke-none",[["stroke","none"]]),e.functional("stroke",a=>{if(a.value){if(a.value.kind==="arbitrary"){let d=a.value.value;switch(a.value.dataType??V(d,["color","number","length","percentage"])){case"number":case"length":case"percentage":return a.modifier?void 0:[o("stroke-width",d)];default:return d=Y(a.value.value,a.modifier,A),d===null?void 0:[o("stroke",d)]}}{let d=Q(a,A,["--stroke","--color"]);if(d)return[o("stroke",d)]}{let d=A.resolve(a.value.value,["--stroke-width"]);if(d)return[o("stroke-width",d)];if(F(a.value.value))return[o("stroke-width",a.value.value)]}}}),t("stroke",()=>[{values:["current","inherit","transparent"],valueThemeKeys:["--stroke","--color"],modifiers:Array.from({length:21},(a,d)=>`${d*5}`)},{values:["0","1","2","3"],valueThemeKeys:["--stroke-width"]}]),j("object-contain",[["object-fit","contain"]]),j("object-cover",[["object-fit","cover"]]),j("object-fill",[["object-fit","fill"]]),j("object-none",[["object-fit","none"]]),j("object-scale-down",[["object-fit","scale-down"]]),j("object-top",[["object-position","top"]]),j("object-top-left",[["object-position","left top"]]),j("object-top-right",[["object-position","right top"]]),j("object-bottom",[["object-position","bottom"]]),j("object-bottom-left",[["object-position","left bottom"]]),j("object-bottom-right",[["object-position","right bottom"]]),j("object-left",[["object-position","left"]]),j("object-right",[["object-position","right"]]),j("object-center",[["object-position","center"]]),r("object",{themeKeys:["--object-position"],handle:a=>[o("object-position",a)]});for(let[a,d]of[["p","padding"],["px","padding-inline"],["py","padding-block"],["ps","padding-inline-start"],["pe","padding-inline-end"],["pt","padding-top"],["pr","padding-right"],["pb","padding-bottom"],["pl","padding-left"]])i(a,["--padding","--spacing"],m=>[o(d,m)]);j("text-left",[["text-align","left"]]),j("text-center",[["text-align","center"]]),j("text-right",[["text-align","right"]]),j("text-justify",[["text-align","justify"]]),j("text-start",[["text-align","start"]]),j("text-end",[["text-align","end"]]),i("indent",["--text-indent","--spacing"],a=>[o("text-indent",a)],{supportsNegative:!0}),j("align-baseline",[["vertical-align","baseline"]]),j("align-top",[["vertical-align","top"]]),j("align-middle",[["vertical-align","middle"]]),j("align-bottom",[["vertical-align","bottom"]]),j("align-text-top",[["vertical-align","text-top"]]),j("align-text-bottom",[["vertical-align","text-bottom"]]),j("align-sub",[["vertical-align","sub"]]),j("align-super",[["vertical-align","super"]]),r("align",{themeKeys:[],handle:a=>[o("vertical-align",a)]}),e.functional("font",a=>{if(!(!a.value||a.modifier)){if(a.value.kind==="arbitrary"){let d=a.value.value;switch(a.value.dataType??V(d,["number","generic-name","family-name"])){case"generic-name":case"family-name":return[o("font-family",d)];default:return[S([_("--tw-font-weight")]),o("--tw-font-weight",d),o("font-weight",d)]}}{let d=A.resolveWith(a.value.value,["--font"],["--font-feature-settings","--font-variation-settings"]);if(d){let[m,B={}]=d;return[o("font-family",m),o("font-feature-settings",B["--font-feature-settings"]),o("font-variation-settings",B["--font-variation-settings"])]}}{let d=A.resolve(a.value.value,["--font-weight"]);if(d)return[S([_("--tw-font-weight")]),o("--tw-font-weight",d),o("font-weight",d)]}}}),t("font",()=>[{values:[],valueThemeKeys:["--font"]},{values:[],valueThemeKeys:["--font-weight"]}]),j("uppercase",[["text-transform","uppercase"]]),j("lowercase",[["text-transform","lowercase"]]),j("capitalize",[["text-transform","capitalize"]]),j("normal-case",[["text-transform","none"]]),j("italic",[["font-style","italic"]]),j("not-italic",[["font-style","normal"]]),j("underline",[["text-decoration-line","underline"]]),j("overline",[["text-decoration-line","overline"]]),j("line-through",[["text-decoration-line","line-through"]]),j("no-underline",[["text-decoration-line","none"]]),j("font-stretch-normal",[["font-stretch","normal"]]),j("font-stretch-ultra-condensed",[["font-stretch","ultra-condensed"]]),j("font-stretch-extra-condensed",[["font-stretch","extra-condensed"]]),j("font-stretch-condensed",[["font-stretch","condensed"]]),j("font-stretch-semi-condensed",[["font-stretch","semi-condensed"]]),j("font-stretch-semi-expanded",[["font-stretch","semi-expanded"]]),j("font-stretch-expanded",[["font-stretch","expanded"]]),j("font-stretch-extra-expanded",[["font-stretch","extra-expanded"]]),j("font-stretch-ultra-expanded",[["font-stretch","ultra-expanded"]]),r("font-stretch",{handleBareValue:({value:a})=>{if(!a.endsWith("%"))return null;let d=Number(a.slice(0,-1));return!F(d)||Number.isNaN(d)||d<50||d>200?null:a},handle:a=>[o("font-stretch",a)]}),t("font-stretch",()=>[{values:["50%","75%","90%","95%","100%","105%","110%","125%","150%","200%"]}]),n("placeholder",{themeKeys:["--background-color","--color"],handle:a=>[X("&::placeholder",[o("--tw-sort","placeholder-color"),o("color",a)])]}),j("decoration-solid",[["text-decoration-style","solid"]]),j("decoration-double",[["text-decoration-style","double"]]),j("decoration-dotted",[["text-decoration-style","dotted"]]),j("decoration-dashed",[["text-decoration-style","dashed"]]),j("decoration-wavy",[["text-decoration-style","wavy"]]),j("decoration-auto",[["text-decoration-thickness","auto"]]),j("decoration-from-font",[["text-decoration-thickness","from-font"]]),e.functional("decoration",a=>{if(a.value){if(a.value.kind==="arbitrary"){let d=a.value.value;switch(a.value.dataType??V(d,["color","length","percentage"])){case"length":case"percentage":return a.modifier?void 0:[o("text-decoration-thickness",d)];default:return d=Y(d,a.modifier,A),d===null?void 0:[o("text-decoration-color",d)]}}{let d=A.resolve(a.value.value,["--text-decoration-thickness"]);if(d)return a.modifier?void 0:[o("text-decoration-thickness",d)];if(F(a.value.value))return a.modifier?void 0:[o("text-decoration-thickness",`${a.value.value}px`)]}{let d=Q(a,A,["--text-decoration-color","--color"]);if(d)return[o("text-decoration-color",d)]}}}),t("decoration",()=>[{values:["current","inherit","transparent"],valueThemeKeys:["--text-decoration-color","--color"],modifiers:Array.from({length:21},(a,d)=>`${d*5}`)},{values:["0","1","2"],valueThemeKeys:["--text-decoration-thickness"]}]),j("animate-none",[["animation","none"]]),r("animate",{themeKeys:["--animate"],handle:a=>[o("animation",a)]});{let a=["var(--tw-blur,)","var(--tw-brightness,)","var(--tw-contrast,)","var(--tw-grayscale,)","var(--tw-hue-rotate,)","var(--tw-invert,)","var(--tw-saturate,)","var(--tw-sepia,)","var(--tw-drop-shadow,)"].join(" "),d=["var(--tw-backdrop-blur,)","var(--tw-backdrop-brightness,)","var(--tw-backdrop-contrast,)","var(--tw-backdrop-grayscale,)","var(--tw-backdrop-hue-rotate,)","var(--tw-backdrop-invert,)","var(--tw-backdrop-opacity,)","var(--tw-backdrop-saturate,)","var(--tw-backdrop-sepia,)"].join(" "),m=()=>S([_("--tw-blur"),_("--tw-brightness"),_("--tw-contrast"),_("--tw-grayscale"),_("--tw-hue-rotate"),_("--tw-invert"),_("--tw-opacity"),_("--tw-saturate"),_("--tw-sepia"),_("--tw-drop-shadow"),_("--tw-drop-shadow-color"),_("--tw-drop-shadow-alpha","100%","<percentage>"),_("--tw-drop-shadow-size")]),B=()=>S([_("--tw-backdrop-blur"),_("--tw-backdrop-brightness"),_("--tw-backdrop-contrast"),_("--tw-backdrop-grayscale"),_("--tw-backdrop-hue-rotate"),_("--tw-backdrop-invert"),_("--tw-backdrop-opacity"),_("--tw-backdrop-saturate"),_("--tw-backdrop-sepia")]);e.functional("filter",k=>{if(!k.modifier){if(k.value===null)return[m(),o("filter",a)];if(k.value.kind==="arbitrary")return[o("filter",k.value.value)];switch(k.value.value){case"none":return[o("filter","none")]}}}),e.functional("backdrop-filter",k=>{if(!k.modifier){if(k.value===null)return[B(),o("-webkit-backdrop-filter",d),o("backdrop-filter",d)];if(k.value.kind==="arbitrary")return[o("-webkit-backdrop-filter",k.value.value),o("backdrop-filter",k.value.value)];switch(k.value.value){case"none":return[o("-webkit-backdrop-filter","none"),o("backdrop-filter","none")]}}}),r("blur",{themeKeys:["--blur"],handle:k=>[m(),o("--tw-blur",`blur(${k})`),o("filter",a)]}),j("blur-none",[m,["--tw-blur"," "],["filter",a]]),r("backdrop-blur",{themeKeys:["--backdrop-blur","--blur"],handle:k=>[B(),o("--tw-backdrop-blur",`blur(${k})`),o("-webkit-backdrop-filter",d),o("backdrop-filter",d)]}),j("backdrop-blur-none",[B,["--tw-backdrop-blur"," "],["-webkit-backdrop-filter",d],["backdrop-filter",d]]),r("brightness",{themeKeys:["--brightness"],handleBareValue:({value:k})=>F(k)?`${k}%`:null,handle:k=>[m(),o("--tw-brightness",`brightness(${k})`),o("filter",a)]}),r("backdrop-brightness",{themeKeys:["--backdrop-brightness","--brightness"],handleBareValue:({value:k})=>F(k)?`${k}%`:null,handle:k=>[B(),o("--tw-backdrop-brightness",`brightness(${k})`),o("-webkit-backdrop-filter",d),o("backdrop-filter",d)]}),t("brightness",()=>[{values:["0","50","75","90","95","100","105","110","125","150","200"],valueThemeKeys:["--brightness"]}]),t("backdrop-brightness",()=>[{values:["0","50","75","90","95","100","105","110","125","150","200"],valueThemeKeys:["--backdrop-brightness","--brightness"]}]),r("contrast",{themeKeys:["--contrast"],handleBareValue:({value:k})=>F(k)?`${k}%`:null,handle:k=>[m(),o("--tw-contrast",`contrast(${k})`),o("filter",a)]}),r("backdrop-contrast",{themeKeys:["--backdrop-contrast","--contrast"],handleBareValue:({value:k})=>F(k)?`${k}%`:null,handle:k=>[B(),o("--tw-backdrop-contrast",`contrast(${k})`),o("-webkit-backdrop-filter",d),o("backdrop-filter",d)]}),t("contrast",()=>[{values:["0","50","75","100","125","150","200"],valueThemeKeys:["--contrast"]}]),t("backdrop-contrast",()=>[{values:["0","50","75","100","125","150","200"],valueThemeKeys:["--backdrop-contrast","--contrast"]}]),r("grayscale",{themeKeys:["--grayscale"],handleBareValue:({value:k})=>F(k)?`${k}%`:null,defaultValue:"100%",handle:k=>[m(),o("--tw-grayscale",`grayscale(${k})`),o("filter",a)]}),r("backdrop-grayscale",{themeKeys:["--backdrop-grayscale","--grayscale"],handleBareValue:({value:k})=>F(k)?`${k}%`:null,defaultValue:"100%",handle:k=>[B(),o("--tw-backdrop-grayscale",`grayscale(${k})`),o("-webkit-backdrop-filter",d),o("backdrop-filter",d)]}),t("grayscale",()=>[{values:["0","25","50","75","100"],valueThemeKeys:["--grayscale"],hasDefaultValue:!0}]),t("backdrop-grayscale",()=>[{values:["0","25","50","75","100"],valueThemeKeys:["--backdrop-grayscale","--grayscale"],hasDefaultValue:!0}]),r("hue-rotate",{supportsNegative:!0,themeKeys:["--hue-rotate"],handleBareValue:({value:k})=>F(k)?`${k}deg`:null,handle:k=>[m(),o("--tw-hue-rotate",`hue-rotate(${k})`),o("filter",a)]}),r("backdrop-hue-rotate",{supportsNegative:!0,themeKeys:["--backdrop-hue-rotate","--hue-rotate"],handleBareValue:({value:k})=>F(k)?`${k}deg`:null,handle:k=>[B(),o("--tw-backdrop-hue-rotate",`hue-rotate(${k})`),o("-webkit-backdrop-filter",d),o("backdrop-filter",d)]}),t("hue-rotate",()=>[{values:["0","15","30","60","90","180"],valueThemeKeys:["--hue-rotate"]}]),t("backdrop-hue-rotate",()=>[{values:["0","15","30","60","90","180"],valueThemeKeys:["--backdrop-hue-rotate","--hue-rotate"]}]),r("invert",{themeKeys:["--invert"],handleBareValue:({value:k})=>F(k)?`${k}%`:null,defaultValue:"100%",handle:k=>[m(),o("--tw-invert",`invert(${k})`),o("filter",a)]}),r("backdrop-invert",{themeKeys:["--backdrop-invert","--invert"],handleBareValue:({value:k})=>F(k)?`${k}%`:null,defaultValue:"100%",handle:k=>[B(),o("--tw-backdrop-invert",`invert(${k})`),o("-webkit-backdrop-filter",d),o("backdrop-filter",d)]}),t("invert",()=>[{values:["0","25","50","75","100"],valueThemeKeys:["--invert"],hasDefaultValue:!0}]),t("backdrop-invert",()=>[{values:["0","25","50","75","100"],valueThemeKeys:["--backdrop-invert","--invert"],hasDefaultValue:!0}]),r("saturate",{themeKeys:["--saturate"],handleBareValue:({value:k})=>F(k)?`${k}%`:null,handle:k=>[m(),o("--tw-saturate",`saturate(${k})`),o("filter",a)]}),r("backdrop-saturate",{themeKeys:["--backdrop-saturate","--saturate"],handleBareValue:({value:k})=>F(k)?`${k}%`:null,handle:k=>[B(),o("--tw-backdrop-saturate",`saturate(${k})`),o("-webkit-backdrop-filter",d),o("backdrop-filter",d)]}),t("saturate",()=>[{values:["0","50","100","150","200"],valueThemeKeys:["--saturate"]}]),t("backdrop-saturate",()=>[{values:["0","50","100","150","200"],valueThemeKeys:["--backdrop-saturate","--saturate"]}]),r("sepia",{themeKeys:["--sepia"],handleBareValue:({value:k})=>F(k)?`${k}%`:null,defaultValue:"100%",handle:k=>[m(),o("--tw-sepia",`sepia(${k})`),o("filter",a)]}),r("backdrop-sepia",{themeKeys:["--backdrop-sepia","--sepia"],handleBareValue:({value:k})=>F(k)?`${k}%`:null,defaultValue:"100%",handle:k=>[B(),o("--tw-backdrop-sepia",`sepia(${k})`),o("-webkit-backdrop-filter",d),o("backdrop-filter",d)]}),t("sepia",()=>[{values:["0","50","100"],valueThemeKeys:["--sepia"],hasDefaultValue:!0}]),t("backdrop-sepia",()=>[{values:["0","50","100"],valueThemeKeys:["--backdrop-sepia","--sepia"],hasDefaultValue:!0}]),j("drop-shadow-none",[m,["--tw-drop-shadow"," "],["filter",a]]),e.functional("drop-shadow",k=>{let v;if(k.modifier&&(k.modifier.kind==="arbitrary"?v=k.modifier.value:F(k.modifier.value)&&(v=`${k.modifier.value}%`)),!k.value){let y=A.get(["--drop-shadow"]),$=A.resolve(null,["--drop-shadow"]);return y===null||$===null?void 0:[m(),o("--tw-drop-shadow-alpha",v),...JA("--tw-drop-shadow-size",y,v,q=>`var(--tw-drop-shadow-color, ${q})`),o("--tw-drop-shadow",H($,",").map(q=>`drop-shadow(${q})`).join(" ")),o("filter",a)]}if(k.value.kind==="arbitrary"){let y=k.value.value;switch(k.value.dataType??V(y,["color"])){case"color":return y=Y(y,k.modifier,A),y===null?void 0:[m(),o("--tw-drop-shadow-color",AA(y,"var(--tw-drop-shadow-alpha)")),o("--tw-drop-shadow","var(--tw-drop-shadow-size)")];default:return k.modifier&&!v?void 0:[m(),o("--tw-drop-shadow-alpha",v),...JA("--tw-drop-shadow-size",y,v,$=>`var(--tw-drop-shadow-color, ${$})`),o("--tw-drop-shadow","var(--tw-drop-shadow-size)"),o("filter",a)]}}{let y=A.get([`--drop-shadow-${k.value.value}`]),$=A.resolve(k.value.value,["--drop-shadow"]);if(y&&$)return k.modifier&&!v?void 0:v?[m(),o("--tw-drop-shadow-alpha",v),...JA("--tw-drop-shadow-size",y,v,q=>`var(--tw-drop-shadow-color, ${q})`),o("--tw-drop-shadow","var(--tw-drop-shadow-size)"),o("filter",a)]:[m(),o("--tw-drop-shadow-alpha",v),...JA("--tw-drop-shadow-size",y,v,q=>`var(--tw-drop-shadow-color, ${q})`),o("--tw-drop-shadow",H($,",").map(q=>`drop-shadow(${q})`).join(" ")),o("filter",a)]}{let y=Q(k,A,["--drop-shadow-color","--color"]);if(y)return y==="inherit"?[m(),o("--tw-drop-shadow-color","inherit"),o("--tw-drop-shadow","var(--tw-drop-shadow-size)")]:[m(),o("--tw-drop-shadow-color",AA(y,"var(--tw-drop-shadow-alpha)")),o("--tw-drop-shadow","var(--tw-drop-shadow-size)")]}}),t("drop-shadow",()=>[{values:["current","inherit","transparent"],valueThemeKeys:["--drop-shadow-color","--color"],modifiers:Array.from({length:21},(k,v)=>`${v*5}`)},{valueThemeKeys:["--drop-shadow"]}]),r("backdrop-opacity",{themeKeys:["--backdrop-opacity","--opacity"],handleBareValue:({value:k})=>NA(k)?`${k}%`:null,handle:k=>[B(),o("--tw-backdrop-opacity",`opacity(${k})`),o("-webkit-backdrop-filter",d),o("backdrop-filter",d)]}),t("backdrop-opacity",()=>[{values:Array.from({length:21},(k,v)=>`${v*5}`),valueThemeKeys:["--backdrop-opacity","--opacity"]}])}{let a=`var(--tw-ease, ${A.resolve(null,["--default-transition-timing-function"])??"ease"})`,d=`var(--tw-duration, ${A.resolve(null,["--default-transition-duration"])??"0s"})`;j("transition-none",[["transition-property","none"]]),j("transition-all",[["transition-property","all"],["transition-timing-function",a],["transition-duration",d]]),j("transition-colors",[["transition-property","color, background-color, border-color, outline-color, text-decoration-color, fill, stroke, --tw-gradient-from, --tw-gradient-via, --tw-gradient-to"],["transition-timing-function",a],["transition-duration",d]]),j("transition-opacity",[["transition-property","opacity"],["transition-timing-function",a],["transition-duration",d]]),j("transition-shadow",[["transition-property","box-shadow"],["transition-timing-function",a],["transition-duration",d]]),j("transition-transform",[["transition-property","transform, translate, scale, rotate"],["transition-timing-function",a],["transition-duration",d]]),r("transition",{defaultValue:"color, background-color, border-color, outline-color, text-decoration-color, fill, stroke, --tw-gradient-from, --tw-gradient-via, --tw-gradient-to, opacity, box-shadow, transform, translate, scale, rotate, filter, -webkit-backdrop-filter, backdrop-filter, display, visibility, content-visibility, overlay, pointer-events",themeKeys:["--transition-property"],handle:m=>[o("transition-property",m),o("transition-timing-function",a),o("transition-duration",d)]}),j("transition-discrete",[["transition-behavior","allow-discrete"]]),j("transition-normal",[["transition-behavior","normal"]]),r("delay",{handleBareValue:({value:m})=>F(m)?`${m}ms`:null,themeKeys:["--transition-delay"],handle:m=>[o("transition-delay",m)]});{let m=()=>S([_("--tw-duration")]);j("duration-initial",[m,["--tw-duration","initial"]]),e.functional("duration",B=>{if(B.modifier||!B.value)return;let k=null;if(B.value.kind==="arbitrary"?k=B.value.value:(k=A.resolve(B.value.fraction??B.value.value,["--transition-duration"]),k===null&&F(B.value.value)&&(k=`${B.value.value}ms`)),k!==null)return[m(),o("--tw-duration",k),o("transition-duration",k)]})}t("delay",()=>[{values:["75","100","150","200","300","500","700","1000"],valueThemeKeys:["--transition-delay"]}]),t("duration",()=>[{values:["75","100","150","200","300","500","700","1000"],valueThemeKeys:["--transition-duration"]}])}{let a=()=>S([_("--tw-ease")]);j("ease-initial",[a,["--tw-ease","initial"]]),j("ease-linear",[a,["--tw-ease","linear"],["transition-timing-function","linear"]]),r("ease",{themeKeys:["--ease"],handle:d=>[a(),o("--tw-ease",d),o("transition-timing-function",d)]})}j("will-change-auto",[["will-change","auto"]]),j("will-change-scroll",[["will-change","scroll-position"]]),j("will-change-contents",[["will-change","contents"]]),j("will-change-transform",[["will-change","transform"]]),r("will-change",{themeKeys:[],handle:a=>[o("will-change",a)]}),j("content-none",[["--tw-content","none"],["content","none"]]),r("content",{themeKeys:[],handle:a=>[S([_("--tw-content",'""')]),o("--tw-content",a),o("content","var(--tw-content)")]});{let a="var(--tw-contain-size,) var(--tw-contain-layout,) var(--tw-contain-paint,) var(--tw-contain-style,)",d=()=>S([_("--tw-contain-size"),_("--tw-contain-layout"),_("--tw-contain-paint"),_("--tw-contain-style")]);j("contain-none",[["contain","none"]]),j("contain-content",[["contain","content"]]),j("contain-strict",[["contain","strict"]]),j("contain-size",[d,["--tw-contain-size","size"],["contain",a]]),j("contain-inline-size",[d,["--tw-contain-size","inline-size"],["contain",a]]),j("contain-layout",[d,["--tw-contain-layout","layout"],["contain",a]]),j("contain-paint",[d,["--tw-contain-paint","paint"],["contain",a]]),j("contain-style",[d,["--tw-contain-style","style"],["contain",a]]),r("contain",{themeKeys:[],handle:m=>[o("contain",m)]})}j("forced-color-adjust-none",[["forced-color-adjust","none"]]),j("forced-color-adjust-auto",[["forced-color-adjust","auto"]]),j("leading-none",[()=>S([_("--tw-leading")]),["--tw-leading","1"],["line-height","1"]]),i("leading",["--leading","--spacing"],a=>[S([_("--tw-leading")]),o("--tw-leading",a),o("line-height",a)]),r("tracking",{supportsNegative:!0,themeKeys:["--tracking"],handle:a=>[S([_("--tw-tracking")]),o("--tw-tracking",a),o("letter-spacing",a)]}),j("antialiased",[["-webkit-font-smoothing","antialiased"],["-moz-osx-font-smoothing","grayscale"]]),j("subpixel-antialiased",[["-webkit-font-smoothing","auto"],["-moz-osx-font-smoothing","auto"]]);{let a="var(--tw-ordinal,) var(--tw-slashed-zero,) var(--tw-numeric-figure,) var(--tw-numeric-spacing,) var(--tw-numeric-fraction,)",d=()=>S([_("--tw-ordinal"),_("--tw-slashed-zero"),_("--tw-numeric-figure"),_("--tw-numeric-spacing"),_("--tw-numeric-fraction")]);j("normal-nums",[["font-variant-numeric","normal"]]),j("ordinal",[d,["--tw-ordinal","ordinal"],["font-variant-numeric",a]]),j("slashed-zero",[d,["--tw-slashed-zero","slashed-zero"],["font-variant-numeric",a]]),j("lining-nums",[d,["--tw-numeric-figure","lining-nums"],["font-variant-numeric",a]]),j("oldstyle-nums",[d,["--tw-numeric-figure","oldstyle-nums"],["font-variant-numeric",a]]),j("proportional-nums",[d,["--tw-numeric-spacing","proportional-nums"],["font-variant-numeric",a]]),j("tabular-nums",[d,["--tw-numeric-spacing","tabular-nums"],["font-variant-numeric",a]]),j("diagonal-fractions",[d,["--tw-numeric-fraction","diagonal-fractions"],["font-variant-numeric",a]]),j("stacked-fractions",[d,["--tw-numeric-fraction","stacked-fractions"],["font-variant-numeric",a]])}{let a=()=>S([_("--tw-outline-style","solid")]);e.static("outline-hidden",()=>[o("--tw-outline-style","none"),o("outline-style","none"),R("@media","(forced-colors: active)",[o("outline","2px solid transparent"),o("outline-offset","2px")])]),j("outline-none",[["--tw-outline-style","none"],["outline-style","none"]]),j("outline-solid",[["--tw-outline-style","solid"],["outline-style","solid"]]),j("outline-dashed",[["--tw-outline-style","dashed"],["outline-style","dashed"]]),j("outline-dotted",[["--tw-outline-style","dotted"],["outline-style","dotted"]]),j("outline-double",[["--tw-outline-style","double"],["outline-style","double"]]),e.functional("outline",d=>{if(d.value===null){if(d.modifier)return;let m=A.get(["--default-outline-width"])??"1px";return[a(),o("outline-style","var(--tw-outline-style)"),o("outline-width",m)]}if(d.value.kind==="arbitrary"){let m=d.value.value;switch(d.value.dataType??V(m,["color","length","number","percentage"])){case"length":case"number":case"percentage":return d.modifier?void 0:[a(),o("outline-style","var(--tw-outline-style)"),o("outline-width",m)];default:return m=Y(m,d.modifier,A),m===null?void 0:[o("outline-color",m)]}}{let m=Q(d,A,["--outline-color","--color"]);if(m)return[o("outline-color",m)]}{if(d.modifier)return;let m=A.resolve(d.value.value,["--outline-width"]);if(m)return[a(),o("outline-style","var(--tw-outline-style)"),o("outline-width",m)];if(F(d.value.value))return[a(),o("outline-style","var(--tw-outline-style)"),o("outline-width",`${d.value.value}px`)]}}),t("outline",()=>[{values:["current","inherit","transparent"],valueThemeKeys:["--outline-color","--color"],modifiers:Array.from({length:21},(d,m)=>`${m*5}`),hasDefaultValue:!0},{values:["0","1","2","4","8"],valueThemeKeys:["--outline-width"]}]),r("outline-offset",{supportsNegative:!0,themeKeys:["--outline-offset"],handleBareValue:({value:d})=>F(d)?`${d}px`:null,handle:d=>[o("outline-offset",d)]}),t("outline-offset",()=>[{supportsNegative:!0,values:["0","1","2","4","8"],valueThemeKeys:["--outline-offset"]}])}r("opacity",{themeKeys:["--opacity"],handleBareValue:({value:a})=>NA(a)?`${a}%`:null,handle:a=>[o("opacity",a)]}),t("opacity",()=>[{values:Array.from({length:21},(a,d)=>`${d*5}`),valueThemeKeys:["--opacity"]}]),j("underline-offset-auto",[["text-underline-offset","auto"]]),r("underline-offset",{supportsNegative:!0,themeKeys:["--text-underline-offset"],handleBareValue:({value:a})=>F(a)?`${a}px`:null,handle:a=>[o("text-underline-offset",a)]}),t("underline-offset",()=>[{supportsNegative:!0,values:["0","1","2","4","8"],valueThemeKeys:["--text-underline-offset"]}]),e.functional("text",a=>{if(a.value){if(a.value.kind==="arbitrary"){let d=a.value.value;switch(a.value.dataType??V(d,["color","length","percentage","absolute-size","relative-size"])){case"size":case"length":case"percentage":case"absolute-size":case"relative-size":{if(a.modifier){let m=a.modifier.kind==="arbitrary"?a.modifier.value:A.resolve(a.modifier.value,["--leading"]);if(!m&&pA(a.modifier.value)){let B=A.resolve(null,["--spacing"]);if(!B)return null;m=`calc(${B} * ${a.modifier.value})`}return!m&&a.modifier.value==="none"&&(m="1"),m?[o("font-size",d),o("line-height",m)]:null}return[o("font-size",d)]}default:return d=Y(d,a.modifier,A),d===null?void 0:[o("color",d)]}}{let d=Q(a,A,["--text-color","--color"]);if(d)return[o("color",d)]}{let d=A.resolveWith(a.value.value,["--text"],["--line-height","--letter-spacing","--font-weight"]);if(d){let[m,B={}]=Array.isArray(d)?d:[d];if(a.modifier){let k=a.modifier.kind==="arbitrary"?a.modifier.value:A.resolve(a.modifier.value,["--leading"]);if(!k&&pA(a.modifier.value)){let y=A.resolve(null,["--spacing"]);if(!y)return null;k=`calc(${y} * ${a.modifier.value})`}if(!k&&a.modifier.value==="none"&&(k="1"),!k)return null;let v=[o("font-size",m)];return k&&v.push(o("line-height",k)),v}return typeof B=="string"?[o("font-size",m),o("line-height",B)]:[o("font-size",m),o("line-height",B["--line-height"]?`var(--tw-leading, ${B["--line-height"]})`:void 0),o("letter-spacing",B["--letter-spacing"]?`var(--tw-tracking, ${B["--letter-spacing"]})`:void 0),o("font-weight",B["--font-weight"]?`var(--tw-font-weight, ${B["--font-weight"]})`:void 0)]}}}}),t("text",()=>[{values:["current","inherit","transparent"],valueThemeKeys:["--text-color","--color"],modifiers:Array.from({length:21},(a,d)=>`${d*5}`)},{values:[],valueThemeKeys:["--text"],modifiers:[],modifierThemeKeys:["--leading"]}]);let L=()=>S([_("--tw-text-shadow-color"),_("--tw-text-shadow-alpha","100%","<percentage>")]);j("text-shadow-initial",[L,["--tw-text-shadow-color","initial"]]),e.functional("text-shadow",a=>{let d;if(a.modifier&&(a.modifier.kind==="arbitrary"?d=a.modifier.value:F(a.modifier.value)&&(d=`${a.modifier.value}%`)),!a.value){let m=A.get(["--text-shadow"]);return m===null?void 0:[L(),o("--tw-text-shadow-alpha",d),...dA("text-shadow",m,d,B=>`var(--tw-text-shadow-color, ${B})`)]}if(a.value.kind==="arbitrary"){let m=a.value.value;switch(a.value.dataType??V(m,["color"])){case"color":return m=Y(m,a.modifier,A),m===null?void 0:[L(),o("--tw-text-shadow-color",AA(m,"var(--tw-text-shadow-alpha)"))];default:return[L(),o("--tw-text-shadow-alpha",d),...dA("text-shadow",m,d,B=>`var(--tw-text-shadow-color, ${B})`)]}}switch(a.value.value){case"none":return a.modifier?void 0:[L(),o("text-shadow","none")];case"inherit":return a.modifier?void 0:[L(),o("--tw-text-shadow-color","inherit")]}{let m=A.get([`--text-shadow-${a.value.value}`]);if(m)return[L(),o("--tw-text-shadow-alpha",d),...dA("text-shadow",m,d,B=>`var(--tw-text-shadow-color, ${B})`)]}{let m=Q(a,A,["--text-shadow-color","--color"]);if(m)return[L(),o("--tw-text-shadow-color",AA(m,"var(--tw-text-shadow-alpha)"))]}}),t("text-shadow",()=>[{values:["current","inherit","transparent"],valueThemeKeys:["--text-shadow-color","--color"],modifiers:Array.from({length:21},(a,d)=>`${d*5}`)},{values:["none"]},{valueThemeKeys:["--text-shadow"],modifiers:Array.from({length:21},(a,d)=>`${d*5}`),hasDefaultValue:A.get(["--text-shadow"])!==null}]);{let a=function($){return`var(--tw-ring-inset,) 0 0 0 calc(${$} + var(--tw-ring-offset-width)) var(--tw-ring-color, ${v})`},d=function($){return`inset 0 0 0 ${$} var(--tw-inset-ring-color, currentcolor)`};var J=a,tA=d;let m=["var(--tw-inset-shadow)","var(--tw-inset-ring-shadow)","var(--tw-ring-offset-shadow)","var(--tw-ring-shadow)","var(--tw-shadow)"].join(", "),B="0 0 #0000",k=()=>S([_("--tw-shadow",B),_("--tw-shadow-color"),_("--tw-shadow-alpha","100%","<percentage>"),_("--tw-inset-shadow",B),_("--tw-inset-shadow-color"),_("--tw-inset-shadow-alpha","100%","<percentage>"),_("--tw-ring-color"),_("--tw-ring-shadow",B),_("--tw-inset-ring-color"),_("--tw-inset-ring-shadow",B),_("--tw-ring-inset"),_("--tw-ring-offset-width","0px","<length>"),_("--tw-ring-offset-color","#fff"),_("--tw-ring-offset-shadow",B)]);j("shadow-initial",[k,["--tw-shadow-color","initial"]]),e.functional("shadow",$=>{let q;if($.modifier&&($.modifier.kind==="arbitrary"?q=$.modifier.value:F($.modifier.value)&&(q=`${$.modifier.value}%`)),!$.value){let T=A.get(["--shadow"]);return T===null?void 0:[k(),o("--tw-shadow-alpha",q),...dA("--tw-shadow",T,q,oA=>`var(--tw-shadow-color, ${oA})`),o("box-shadow",m)]}if($.value.kind==="arbitrary"){let T=$.value.value;switch($.value.dataType??V(T,["color"])){case"color":return T=Y(T,$.modifier,A),T===null?void 0:[k(),o("--tw-shadow-color",AA(T,"var(--tw-shadow-alpha)"))];default:return[k(),o("--tw-shadow-alpha",q),...dA("--tw-shadow",T,q,oA=>`var(--tw-shadow-color, ${oA})`),o("box-shadow",m)]}}switch($.value.value){case"none":return $.modifier?void 0:[k(),o("--tw-shadow",B),o("box-shadow",m)];case"inherit":return $.modifier?void 0:[k(),o("--tw-shadow-color","inherit")]}{let T=A.get([`--shadow-${$.value.value}`]);if(T)return[k(),o("--tw-shadow-alpha",q),...dA("--tw-shadow",T,q,oA=>`var(--tw-shadow-color, ${oA})`),o("box-shadow",m)]}{let T=Q($,A,["--box-shadow-color","--color"]);if(T)return[k(),o("--tw-shadow-color",AA(T,"var(--tw-shadow-alpha)"))]}}),t("shadow",()=>[{values:["current","inherit","transparent"],valueThemeKeys:["--box-shadow-color","--color"],modifiers:Array.from({length:21},($,q)=>`${q*5}`)},{values:["none"]},{valueThemeKeys:["--shadow"],modifiers:Array.from({length:21},($,q)=>`${q*5}`),hasDefaultValue:A.get(["--shadow"])!==null}]),j("inset-shadow-initial",[k,["--tw-inset-shadow-color","initial"]]),e.functional("inset-shadow",$=>{let q;if($.modifier&&($.modifier.kind==="arbitrary"?q=$.modifier.value:F($.modifier.value)&&(q=`${$.modifier.value}%`)),!$.value){let T=A.get(["--inset-shadow"]);return T===null?void 0:[k(),o("--tw-inset-shadow-alpha",q),...dA("--tw-inset-shadow",T,q,oA=>`var(--tw-inset-shadow-color, ${oA})`),o("box-shadow",m)]}if($.value.kind==="arbitrary"){let T=$.value.value;switch($.value.dataType??V(T,["color"])){case"color":return T=Y(T,$.modifier,A),T===null?void 0:[k(),o("--tw-inset-shadow-color",AA(T,"var(--tw-inset-shadow-alpha)"))];default:return[k(),o("--tw-inset-shadow-alpha",q),...dA("--tw-inset-shadow",T,q,oA=>`var(--tw-inset-shadow-color, ${oA})`,"inset "),o("box-shadow",m)]}}switch($.value.value){case"none":return $.modifier?void 0:[k(),o("--tw-inset-shadow",B),o("box-shadow",m)];case"inherit":return $.modifier?void 0:[k(),o("--tw-inset-shadow-color","inherit")]}{let T=A.get([`--inset-shadow-${$.value.value}`]);if(T)return[k(),o("--tw-inset-shadow-alpha",q),...dA("--tw-inset-shadow",T,q,oA=>`var(--tw-inset-shadow-color, ${oA})`),o("box-shadow",m)]}{let T=Q($,A,["--box-shadow-color","--color"]);if(T)return[k(),o("--tw-inset-shadow-color",AA(T,"var(--tw-inset-shadow-alpha)"))]}}),t("inset-shadow",()=>[{values:["current","inherit","transparent"],valueThemeKeys:["--box-shadow-color","--color"],modifiers:Array.from({length:21},($,q)=>`${q*5}`)},{values:["none"]},{valueThemeKeys:["--inset-shadow"],modifiers:Array.from({length:21},($,q)=>`${q*5}`),hasDefaultValue:A.get(["--inset-shadow"])!==null}]),j("ring-inset",[k,["--tw-ring-inset","inset"]]);let v=A.get(["--default-ring-color"])??"currentcolor";e.functional("ring",$=>{if(!$.value){if($.modifier)return;let q=A.get(["--default-ring-width"])??"1px";return[k(),o("--tw-ring-shadow",a(q)),o("box-shadow",m)]}if($.value.kind==="arbitrary"){let q=$.value.value;switch($.value.dataType??V(q,["color","length"])){case"length":return $.modifier?void 0:[k(),o("--tw-ring-shadow",a(q)),o("box-shadow",m)];default:return q=Y(q,$.modifier,A),q===null?void 0:[o("--tw-ring-color",q)]}}{let q=Q($,A,["--ring-color","--color"]);if(q)return[o("--tw-ring-color",q)]}{if($.modifier)return;let q=A.resolve($.value.value,["--ring-width"]);if(q===null&&F($.value.value)&&(q=`${$.value.value}px`),q)return[k(),o("--tw-ring-shadow",a(q)),o("box-shadow",m)]}}),t("ring",()=>[{values:["current","inherit","transparent"],valueThemeKeys:["--ring-color","--color"],modifiers:Array.from({length:21},($,q)=>`${q*5}`)},{values:["0","1","2","4","8"],valueThemeKeys:["--ring-width"],hasDefaultValue:!0}]),e.functional("inset-ring",$=>{if(!$.value)return $.modifier?void 0:[k(),o("--tw-inset-ring-shadow",d("1px")),o("box-shadow",m)];if($.value.kind==="arbitrary"){let q=$.value.value;switch($.value.dataType??V(q,["color","length"])){case"length":return $.modifier?void 0:[k(),o("--tw-inset-ring-shadow",d(q)),o("box-shadow",m)];default:return q=Y(q,$.modifier,A),q===null?void 0:[o("--tw-inset-ring-color",q)]}}{let q=Q($,A,["--ring-color","--color"]);if(q)return[o("--tw-inset-ring-color",q)]}{if($.modifier)return;let q=A.resolve($.value.value,["--ring-width"]);if(q===null&&F($.value.value)&&(q=`${$.value.value}px`),q)return[k(),o("--tw-inset-ring-shadow",d(q)),o("box-shadow",m)]}}),t("inset-ring",()=>[{values:["current","inherit","transparent"],valueThemeKeys:["--ring-color","--color"],modifiers:Array.from({length:21},($,q)=>`${q*5}`)},{values:["0","1","2","4","8"],valueThemeKeys:["--ring-width"],hasDefaultValue:!0}]);let y="var(--tw-ring-inset,) 0 0 0 var(--tw-ring-offset-width) var(--tw-ring-offset-color)";e.functional("ring-offset",$=>{if($.value){if($.value.kind==="arbitrary"){let q=$.value.value;switch($.value.dataType??V(q,["color","length"])){case"length":return $.modifier?void 0:[o("--tw-ring-offset-width",q),o("--tw-ring-offset-shadow",y)];default:return q=Y(q,$.modifier,A),q===null?void 0:[o("--tw-ring-offset-color",q)]}}{let q=A.resolve($.value.value,["--ring-offset-width"]);if(q)return $.modifier?void 0:[o("--tw-ring-offset-width",q),o("--tw-ring-offset-shadow",y)];if(F($.value.value))return $.modifier?void 0:[o("--tw-ring-offset-width",`${$.value.value}px`),o("--tw-ring-offset-shadow",y)]}{let q=Q($,A,["--ring-offset-color","--color"]);if(q)return[o("--tw-ring-offset-color",q)]}}})}return t("ring-offset",()=>[{values:["current","inherit","transparent"],valueThemeKeys:["--ring-offset-color","--color"],modifiers:Array.from({length:21},(a,d)=>`${d*5}`)},{values:["0","1","2","4","8"],valueThemeKeys:["--ring-offset-width"]}]),e.functional("@container",a=>{let d=null;if(a.value===null?d="inline-size":a.value.kind==="arbitrary"?d=a.value.value:a.value.kind==="named"&&a.value.value==="normal"&&(d="normal"),d!==null)return a.modifier?[o("container-type",d),o("container-name",a.modifier.value)]:[o("container-type",d)]}),t("@container",()=>[{values:["normal"],valueThemeKeys:[],hasDefaultValue:!0}]),e}var qj=["number","integer","ratio","percentage"];function Rr(A){let e=A.params;return Cr.test(e)?t=>{let j={"--value":{usedSpacingInteger:!1,usedSpacingNumber:!1,themeKeys:new Set,literals:new Set},"--modifier":{usedSpacingInteger:!1,usedSpacingNumber:!1,themeKeys:new Set,literals:new Set}};z(A.nodes,r=>{if(r.kind!=="declaration"||!r.value||!r.value.includes("--value(")&&!r.value.includes("--modifier("))return;let n=W(r.value);rA(n,i=>{if(i.kind!=="function")return;if(i.value==="--spacing"&&!(j["--modifier"].usedSpacingNumber&&j["--value"].usedSpacingNumber))return rA(i.nodes,c=>{var h,f;if(c.kind!=="function"||c.value!=="--value"&&c.value!=="--modifier")return;let s=c.value;for(let u of c.nodes)if(u.kind==="word"){if(u.value==="integer")(h=j[s]).usedSpacingInteger||(h.usedSpacingInteger=!0);else if(u.value==="number"&&((f=j[s]).usedSpacingNumber||(f.usedSpacingNumber=!0),j["--modifier"].usedSpacingNumber&&j["--value"].usedSpacingNumber))return 2}}),0;if(i.value!=="--value"&&i.value!=="--modifier")return;let l=H(jA(i.nodes),",");for(let[c,s]of l.entries())s=s.replace(/\\\*/g,"*"),s=s.replace(/--(.*?)\s--(.*?)/g,"--$1-*--$2"),s=s.replace(/\s+/g,""),s=s.replace(/(-\*){2,}/g,"-*"),s[0]==="-"&&s[1]==="-"&&!s.includes("-*")&&(s+="-*"),l[c]=s;i.nodes=W(l.join(","));for(let c of i.nodes)if(c.kind==="word"&&(c.value[0]==='"'||c.value[0]==="'")&&c.value[0]===c.value[c.value.length-1]){let s=c.value.slice(1,-1);j[i.value].literals.add(s)}else if(c.kind==="word"&&c.value[0]==="-"&&c.value[1]==="-"){let s=c.value.replace(/-\*.*$/g,"");j[i.value].themeKeys.add(s)}else if(c.kind==="word"&&!(c.value[0]==="["&&c.value[c.value.length-1]==="]")&&!qj.includes(c.value)){console.warn(`Unsupported bare value data type: "${c.value}".
Only valid data types are: ${qj.map(E=>`"${E}"`).join(", ")}.
`);let s=c.value,h=structuredClone(i),f="\xB6";rA(h.nodes,(E,{replaceWith:x})=>{E.kind==="word"&&E.value===s&&x({kind:"word",value:f})});let u="^".repeat(jA([c]).length),g=jA([h]).indexOf(f),p=["```css",jA([i])," ".repeat(g)+u,"```"].join(`
`);console.warn(p)}}),r.value=jA(n)}),t.utilities.functional(e.slice(0,-2),r=>{let n=structuredClone(A),i=r.value,l=r.modifier;if(i===null)return;let c=!1,s=!1,h=!1,f=!1,u=new Map,g=!1;if(z([n],(p,{parent:E,replaceWith:x})=>{if(E?.kind!=="rule"&&E?.kind!=="at-rule"||p.kind!=="declaration"||!p.value)return;let I=W(p.value);(rA(I,(b,{replaceWith:G})=>{if(b.kind==="function"){if(b.value==="--value"){c=!0;let w=Oe(i,b,t);return w?(s=!0,w.ratio?g=!0:u.set(p,E),G(w.nodes),1):(c||(c=!1),x([]),2)}else if(b.value==="--modifier"){if(l===null)return x([]),2;h=!0;let w=Oe(l,b,t);return w?(f=!0,G(w.nodes),1):(h||(h=!1),x([]),2)}}})??0)===0&&(p.value=jA(I))}),c&&!s||h&&!f||g&&f||l&&!g&&!f)return null;if(g)for(let[p,E]of u){let x=E.nodes.indexOf(p);x!==-1&&E.nodes.splice(x,1)}return n.nodes}),t.utilities.suggest(e.slice(0,-2),()=>{let r=[],n=[];for(let[i,{literals:l,usedSpacingNumber:c,usedSpacingInteger:s,themeKeys:h}]of[[r,j["--value"]],[n,j["--modifier"]]]){for(let f of l)i.push(f);if(c)i.push(...tj);else if(s)for(let f of tj)F(f)&&i.push(f);for(let f of t.theme.keysInNamespaces(h))i.push(f.replace(ft,(u,g,p)=>`${g}.${p}`))}return[{values:r,modifiers:n}]})}:Sr.test(e)?t=>{t.utilities.static(e,()=>structuredClone(A.nodes))}:null}function Oe(A,e,t){for(let j of e.nodes){if(A.kind==="named"&&j.kind==="word"&&(j.value[0]==="'"||j.value[0]==='"')&&j.value[j.value.length-1]===j.value[0]&&j.value.slice(1,-1)===A.value)return{nodes:W(A.value)};if(A.kind==="named"&&j.kind==="word"&&j.value[0]==="-"&&j.value[1]==="-"){let r=j.value;if(r.endsWith("-*")){r=r.slice(0,-2);let n=t.theme.resolve(A.value,[r]);if(n)return{nodes:W(n)}}else{let n=r.split("-*");if(n.length<=1)continue;let i=[n.shift()],l=t.theme.resolveWith(A.value,i,n);if(l){let[,c={}]=l;{let s=c[n.pop()];if(s)return{nodes:W(s)}}}}}else if(A.kind==="named"&&j.kind==="word"){if(!qj.includes(j.value))continue;let r=j.value==="ratio"&&"fraction"in A?A.fraction:A.value;if(!r)continue;let n=V(r,[j.value]);if(n===null)continue;if(n==="ratio"){let[i,l]=H(r,"/");if(!F(i)||!F(l))continue}else if(n==="number"&&!pA(r)||n==="percentage"&&!F(r.slice(0,-1)))continue;return{nodes:W(r),ratio:n==="ratio"}}else if(A.kind==="arbitrary"&&j.kind==="word"&&j.value[0]==="["&&j.value[j.value.length-1]==="]"){let r=j.value.slice(1,-1);if(r==="*")return{nodes:W(A.value)};if("dataType"in A&&A.dataType&&A.dataType!==r)continue;if("dataType"in A&&A.dataType)return{nodes:W(A.value)};if(V(A.value,[r])!==null)return{nodes:W(A.value)}}}}function dA(A,e,t,j,r=""){let n=!1,i=ej(e,c=>t==null?j(c):c.startsWith("current")?j(AA(c,t)):((c.startsWith("var(")||t.startsWith("var("))&&(n=!0),j(dt(c,t))));function l(c){return r?H(c,",").map(s=>r+s).join(","):c}return n?[o(A,l(ej(e,j))),Z("@supports (color: lab(from red l a b))",[o(A,l(i))])]:[o(A,l(i))]}function JA(A,e,t,j,r=""){let n=!1,i=H(e,",").map(l=>ej(l,c=>t==null?j(c):c.startsWith("current")?j(AA(c,t)):((c.startsWith("var(")||t.startsWith("var("))&&(n=!0),j(dt(c,t))))).map(l=>`drop-shadow(${l})`).join(" ");return n?[o(A,r+H(e,",").map(l=>`drop-shadow(${ej(l,j)})`).join(" ")),Z("@supports (color: lab(from red l a b))",[o(A,r+i)])]:[o(A,r+i)]}var Gj={"--alpha":Xr,"--spacing":Vr,"--theme":Jr,theme:Ur};function Xr(A,e,t,...j){let[r,n]=H(t,"/").map(i=>i.trim());if(!r||!n)throw new Error(`The --alpha(\u2026) function requires a color and an alpha value, e.g.: \`--alpha(${r||"var(--my-color)"} / ${n||"50%"})\``);if(j.length>0)throw new Error(`The --alpha(\u2026) function only accepts one argument, e.g.: \`--alpha(${r||"var(--my-color)"} / ${n||"50%"})\``);return AA(r,n)}function Vr(A,e,t,...j){if(!t)throw new Error("The --spacing(\u2026) function requires an argument, but received none.");if(j.length>0)throw new Error(`The --spacing(\u2026) function only accepts a single argument, but received ${j.length+1}.`);let r=A.theme.resolve(null,["--spacing"]);if(!r)throw new Error("The --spacing(\u2026) function requires that the `--spacing` theme variable exists, but it was not found.");return`calc(${r} * ${t})`}function Jr(A,e,t,...j){if(!t.startsWith("--"))throw new Error("The --theme(\u2026) function can only be used with CSS variables from your theme.");let r=!1;t.endsWith(" inline")&&(r=!0,t=t.slice(0,-7)),e.kind==="at-rule"&&(r=!0);let n=A.resolveThemeValue(t,r);if(!n){if(j.length>0)return j.join(", ");throw new Error(`Could not resolve value for theme function: \`theme(${t})\`. Consider checking if the variable name is correct or provide a fallback value to silence this error.`)}if(j.length===0)return n;let i=j.join(", ");if(i==="initial")return n;if(n==="initial")return i;if(n.startsWith("var(")||n.startsWith("theme(")||n.startsWith("--theme(")){let l=W(n);return Yr(l,i),jA(l)}return n}function Ur(A,e,t,...j){t=Wr(t);let r=A.resolveThemeValue(t);if(!r&&j.length>0)return j.join(", ");if(!r)throw new Error(`Could not resolve value for theme function: \`theme(${t})\`. Consider checking if the path is correct or provide a fallback value to silence this error.`);return r}var Ke=new RegExp(Object.keys(Gj).map(A=>`${A}\\(`).join("|"));function Tj(A,e){let t=0;return z(A,j=>{if(j.kind==="declaration"&&j.value&&Ke.test(j.value)){t|=8,j.value=Pe(j.value,j,e);return}j.kind==="at-rule"&&(j.name==="@media"||j.name==="@custom-media"||j.name==="@container"||j.name==="@supports")&&Ke.test(j.params)&&(t|=8,j.params=Pe(j.params,j,e))}),t}function Pe(A,e,t){let j=W(A);return rA(j,(r,{replaceWith:n})=>{if(r.kind==="function"&&r.value in Gj){let i=H(jA(r.nodes).trim(),",").map(c=>c.trim()),l=Gj[r.value](t,e,...i);return n(W(l))}}),jA(j)}function Wr(A){if(A[0]!=="'"&&A[0]!=='"')return A;let e="",t=A[0];for(let j=1;j<A.length-1;j++){let r=A[j],n=A[j+1];r==="\\"&&(n===t||n==="\\")?(e+=n,j++):e+=r}return e}function Yr(A,e){rA(A,t=>{if(t.kind==="function"&&!(t.value!=="var"&&t.value!=="theme"&&t.value!=="--theme"))if(t.nodes.length===1)t.nodes.push({kind:"word",value:`, ${e}`});else{let j=t.nodes[t.nodes.length-1];j.kind==="word"&&j.value==="initial"&&(j.value=e)}})}function ht(A,e){let t=A.length,j=e.length,r=t<j?t:j;for(let n=0;n<r;n++){let i=A.charCodeAt(n),l=e.charCodeAt(n);if(i>=48&&i<=57&&l>=48&&l<=57){let c=n,s=n+1,h=n,f=n+1;for(i=A.charCodeAt(s);i>=48&&i<=57;)i=A.charCodeAt(++s);for(l=e.charCodeAt(f);l>=48&&l<=57;)l=e.charCodeAt(++f);let u=A.slice(c,s),g=e.slice(h,f),p=Number(u)-Number(g);if(p)return p;if(u<g)return-1;if(u>g)return 1;continue}if(i!==l)return i-l}return A.length-e.length}var Zr=/^\d+\/\d+$/;function Qr(A){let e=new U(j=>({name:j,utility:j,fraction:!1,modifiers:[]}));for(let j of A.utilities.keys("static")){let r=e.get(j);r.fraction=!1,r.modifiers=[]}for(let j of A.utilities.keys("functional")){let r=A.utilities.getCompletions(j);for(let n of r)for(let i of n.values){let l=i!==null&&Zr.test(i),c=i===null?j:`${j}-${i}`,s=e.get(c);if(s.utility=j,s.fraction||(s.fraction=l),s.modifiers.push(...n.modifiers),n.supportsNegative){let h=e.get(`-${c}`);h.utility=`-${j}`,h.fraction||(h.fraction=l),h.modifiers.push(...n.modifiers)}}}if(e.size===0)return[];let t=Array.from(e.values());return t.sort((j,r)=>ht(j.name,r.name)),Aa(t)}function Aa(A){let e=[],t=null,j=new Map,r=new U(()=>[]);for(let i of A){let{utility:l,fraction:c}=i;t||(t={utility:l,items:[]},j.set(l,t)),l!==t.utility&&(e.push(t),t={utility:l,items:[]},j.set(l,t)),c?r.get(l).push(i):t.items.push(i)}t&&e[e.length-1]!==t&&e.push(t);for(let[i,l]of r){let c=j.get(i);c&&c.items.push(...l)}let n=[];for(let i of e)for(let l of i.items)n.push([l.name,{modifiers:l.modifiers}]);return n}function ja(A){let e=[];for(let[j,r]of A.variants.entries()){let n=function({value:c,modifier:s}={}){let h=j;c&&(h+=i?`-${c}`:c),s&&(h+=`/${s}`);let f=A.parseVariant(h);if(!f)return[];let u=X(".__placeholder__",[]);if(cj(u,f,A.variants)===null)return[];let g=[];return _j(u.nodes,(p,{path:E})=>{if(p.kind!=="rule"&&p.kind!=="at-rule"||p.nodes.length>0)return;E.sort((b,G)=>{let w=b.kind==="at-rule",P=G.kind==="at-rule";return w&&!P?-1:!w&&P?1:0});let x=E.flatMap(b=>b.kind==="rule"?b.selector==="&"?[]:[b.selector]:b.kind==="at-rule"?[`${b.name} ${b.params}`]:[]),I="";for(let b=x.length-1;b>=0;b--)I=I===""?x[b]:`${x[b]} { ${I} }`;g.push(I)}),g};var t=n;if(r.kind==="arbitrary")continue;let i=j!=="@",l=A.variants.getCompletions(j);switch(r.kind){case"static":{e.push({name:j,values:l,isArbitrary:!1,hasDash:i,selectors:n});break}case"functional":{e.push({name:j,values:l,isArbitrary:!0,hasDash:i,selectors:n});break}case"compound":{e.push({name:j,values:l,isArbitrary:!0,hasDash:i,selectors:n});break}}}return e}function ea(A,e){let{astNodes:t,nodeSorting:j}=ij(Array.from(e),A),r=new Map(e.map(i=>[i,null])),n=0n;for(let i of t){let l=j.get(i)?.candidate;l&&r.set(l,r.get(l)??n++)}return e.map(i=>[i,r.get(i)??null])}var ut=/^@?[a-zA-Z0-9_-]*$/,ta=class{constructor(){nA(this,"compareFns",new Map);nA(this,"variants",new Map);nA(this,"completions",new Map);nA(this,"groupOrder",null);nA(this,"lastOrder",0)}static(A,e,{compounds:t,order:j}={}){this.set(A,{kind:"static",applyFn:e,compoundsWith:0,compounds:t??2,order:j})}fromAst(A,e){let t=[];z(e,j=>{j.kind==="rule"?t.push(j.selector):j.kind==="at-rule"&&j.name!=="@slot"&&t.push(`${j.name} ${j.params}`)}),this.static(A,j=>{let r=structuredClone(e);mt(r,j.nodes),j.nodes=r},{compounds:KA(t)})}functional(A,e,{compounds:t,order:j}={}){this.set(A,{kind:"functional",applyFn:e,compoundsWith:0,compounds:t??2,order:j})}compound(A,e,t,{compounds:j,order:r}={}){this.set(A,{kind:"compound",applyFn:t,compoundsWith:e,compounds:j??2,order:r})}group(A,e){this.groupOrder=this.nextOrder(),e&&this.compareFns.set(this.groupOrder,e),A(),this.groupOrder=null}has(A){return this.variants.has(A)}get(A){return this.variants.get(A)}kind(A){return this.variants.get(A)?.kind}compoundsWith(A,e){let t=this.variants.get(A),j=typeof e=="string"?this.variants.get(e):e.kind==="arbitrary"?{compounds:KA([e.selector])}:this.variants.get(e.root);return!(!t||!j||t.kind!=="compound"||j.compounds===0||t.compoundsWith===0||(t.compoundsWith&j.compounds)===0)}suggest(A,e){this.completions.set(A,e)}getCompletions(A){return this.completions.get(A)?.()??[]}compare(A,e){if(A===e)return 0;if(A===null)return-1;if(e===null)return 1;if(A.kind==="arbitrary"&&e.kind==="arbitrary")return A.selector<e.selector?-1:1;if(A.kind==="arbitrary")return 1;if(e.kind==="arbitrary")return-1;let t=this.variants.get(A.root).order,j=this.variants.get(e.root).order,r=t-j;if(r!==0)return r;if(A.kind==="compound"&&e.kind==="compound"){let c=this.compare(A.variant,e.variant);return c!==0?c:A.modifier&&e.modifier?A.modifier.value<e.modifier.value?-1:1:A.modifier?1:e.modifier?-1:0}let n=this.compareFns.get(t);if(n!==void 0)return n(A,e);if(A.root!==e.root)return A.root<e.root?-1:1;let i=A.value,l=e.value;return i===null?-1:l===null||i.kind==="arbitrary"&&l.kind!=="arbitrary"?1:i.kind!=="arbitrary"&&l.kind==="arbitrary"||i.value<l.value?-1:1}keys(){return this.variants.keys()}entries(){return this.variants.entries()}set(A,{kind:e,applyFn:t,compounds:j,compoundsWith:r,order:n}){let i=this.variants.get(A);i?Object.assign(i,{kind:e,applyFn:t,compounds:j}):(n===void 0&&(this.lastOrder=this.nextOrder(),n=this.lastOrder),this.variants.set(A,{kind:e,applyFn:t,order:n,compoundsWith:r,compounds:j}))}nextOrder(){return this.groupOrder??this.lastOrder+1}};function KA(A){let e=0;for(let t of A){if(t[0]==="@"){if(!t.startsWith("@media")&&!t.startsWith("@supports")&&!t.startsWith("@container"))return 0;e|=1;continue}if(t.includes("::"))return 0;e|=2}return e}function ra(A){let e=new ta;function t(s,h,{compounds:f}={}){f=f??KA(h),e.static(s,u=>{u.nodes=h.map(g=>Z(g,u.nodes))},{compounds:f})}t("*",[":is(& > *)"],{compounds:0}),t("**",[":is(& *)"],{compounds:0});function j(s,h){return h.map(f=>{f=f.trim();let u=H(f," ");return u[0]==="not"?u.slice(1).join(" "):s==="@container"?u[0][0]==="("?`not ${f}`:u[1]==="not"?`${u[0]} ${u.slice(2).join(" ")}`:`${u[0]} not ${u.slice(1).join(" ")}`:`not ${f}`})}let r=["@media","@supports","@container"];function n(s){for(let h of r){if(h!==s.name)continue;let f=H(s.params,",");return f.length>1?null:(f=j(s.name,f),R(s.name,f.join(", ")))}return null}function i(s){return s.includes("::")?null:`&:not(${H(s,",").map(h=>(h=h.replaceAll("&","*"),h)).join(", ")})`}e.compound("not",3,(s,h)=>{if(h.variant.kind==="arbitrary"&&h.variant.relative||h.modifier)return null;let f=!1;if(z([s],(u,{path:g})=>{if(u.kind!=="rule"&&u.kind!=="at-rule"||u.nodes.length>0)return 0;let p=[],E=[];for(let I of g)I.kind==="at-rule"?p.push(I):I.kind==="rule"&&E.push(I);if(p.length>1||E.length>1)return 2;let x=[];for(let I of E){let b=i(I.selector);if(!b)return f=!1,2;x.push(X(b,[]))}for(let I of p){let b=n(I);if(!b)return f=!1,2;x.push(b)}return Object.assign(s,X("&",x)),f=!0,1}),s.kind==="rule"&&s.selector==="&"&&s.nodes.length===1&&Object.assign(s,s.nodes[0]),!f)return null}),e.suggest("not",()=>Array.from(e.keys()).filter(s=>e.compoundsWith("not",s))),e.compound("group",2,(s,h)=>{if(h.variant.kind==="arbitrary"&&h.variant.relative)return null;let f=h.modifier?`:where(.${A.prefix?`${A.prefix}\\:`:""}group\\/${h.modifier.value})`:`:where(.${A.prefix?`${A.prefix}\\:`:""}group)`,u=!1;if(z([s],(g,{path:p})=>{if(g.kind!=="rule")return 0;for(let x of p.slice(0,-1))if(x.kind==="rule")return u=!1,2;let E=g.selector.replaceAll("&",f);H(E,",").length>1&&(E=`:is(${E})`),g.selector=`&:is(${E} *)`,u=!0}),!u)return null}),e.suggest("group",()=>Array.from(e.keys()).filter(s=>e.compoundsWith("group",s))),e.compound("peer",2,(s,h)=>{if(h.variant.kind==="arbitrary"&&h.variant.relative)return null;let f=h.modifier?`:where(.${A.prefix?`${A.prefix}\\:`:""}peer\\/${h.modifier.value})`:`:where(.${A.prefix?`${A.prefix}\\:`:""}peer)`,u=!1;if(z([s],(g,{path:p})=>{if(g.kind!=="rule")return 0;for(let x of p.slice(0,-1))if(x.kind==="rule")return u=!1,2;let E=g.selector.replaceAll("&",f);H(E,",").length>1&&(E=`:is(${E})`),g.selector=`&:is(${E} ~ *)`,u=!0}),!u)return null}),e.suggest("peer",()=>Array.from(e.keys()).filter(s=>e.compoundsWith("peer",s))),t("first-letter",["&::first-letter"]),t("first-line",["&::first-line"]),t("marker",["& *::marker","&::marker","& *::-webkit-details-marker","&::-webkit-details-marker"]),t("selection",["& *::selection","&::selection"]),t("file",["&::file-selector-button"]),t("placeholder",["&::placeholder"]),t("backdrop",["&::backdrop"]),t("details-content",["&::details-content"]);{let s=function(){return S([R("@property","--tw-content",[o("syntax",'"*"'),o("initial-value",'""'),o("inherits","false")])])};var l=s;e.static("before",h=>{h.nodes=[X("&::before",[s(),o("content","var(--tw-content)"),...h.nodes])]},{compounds:0}),e.static("after",h=>{h.nodes=[X("&::after",[s(),o("content","var(--tw-content)"),...h.nodes])]},{compounds:0})}t("first",["&:first-child"]),t("last",["&:last-child"]),t("only",["&:only-child"]),t("odd",["&:nth-child(odd)"]),t("even",["&:nth-child(even)"]),t("first-of-type",["&:first-of-type"]),t("last-of-type",["&:last-of-type"]),t("only-of-type",["&:only-of-type"]),t("visited",["&:visited"]),t("target",["&:target"]),t("open",["&:is([open], :popover-open, :open)"]),t("default",["&:default"]),t("checked",["&:checked"]),t("indeterminate",["&:indeterminate"]),t("placeholder-shown",["&:placeholder-shown"]),t("autofill",["&:autofill"]),t("optional",["&:optional"]),t("required",["&:required"]),t("valid",["&:valid"]),t("invalid",["&:invalid"]),t("user-valid",["&:user-valid"]),t("user-invalid",["&:user-invalid"]),t("in-range",["&:in-range"]),t("out-of-range",["&:out-of-range"]),t("read-only",["&:read-only"]),t("empty",["&:empty"]),t("focus-within",["&:focus-within"]),e.static("hover",s=>{s.nodes=[X("&:hover",[R("@media","(hover: hover)",s.nodes)])]}),t("focus",["&:focus"]),t("focus-visible",["&:focus-visible"]),t("active",["&:active"]),t("enabled",["&:enabled"]),t("disabled",["&:disabled"]),t("inert",["&:is([inert], [inert] *)"]),e.compound("in",2,(s,h)=>{if(h.modifier)return null;let f=!1;if(z([s],(u,{path:g})=>{if(u.kind!=="rule")return 0;for(let p of g.slice(0,-1))if(p.kind==="rule")return f=!1,2;u.selector=`:where(${u.selector.replaceAll("&","*")}) &`,f=!0}),!f)return null}),e.suggest("in",()=>Array.from(e.keys()).filter(s=>e.compoundsWith("in",s))),e.compound("has",2,(s,h)=>{if(h.modifier)return null;let f=!1;if(z([s],(u,{path:g})=>{if(u.kind!=="rule")return 0;for(let p of g.slice(0,-1))if(p.kind==="rule")return f=!1,2;u.selector=`&:has(${u.selector.replaceAll("&","*")})`,f=!0}),!f)return null}),e.suggest("has",()=>Array.from(e.keys()).filter(s=>e.compoundsWith("has",s))),e.functional("aria",(s,h)=>{if(!h.value||h.modifier)return null;h.value.kind==="arbitrary"?s.nodes=[X(`&[aria-${De(h.value.value)}]`,s.nodes)]:s.nodes=[X(`&[aria-${h.value.value}="true"]`,s.nodes)]}),e.suggest("aria",()=>["busy","checked","disabled","expanded","hidden","pressed","readonly","required","selected"]),e.functional("data",(s,h)=>{if(!h.value||h.modifier)return null;s.nodes=[X(`&[data-${De(h.value.value)}]`,s.nodes)]}),e.functional("nth",(s,h)=>{if(!h.value||h.modifier||h.value.kind==="named"&&!F(h.value.value))return null;s.nodes=[X(`&:nth-child(${h.value.value})`,s.nodes)]}),e.functional("nth-last",(s,h)=>{if(!h.value||h.modifier||h.value.kind==="named"&&!F(h.value.value))return null;s.nodes=[X(`&:nth-last-child(${h.value.value})`,s.nodes)]}),e.functional("nth-of-type",(s,h)=>{if(!h.value||h.modifier||h.value.kind==="named"&&!F(h.value.value))return null;s.nodes=[X(`&:nth-of-type(${h.value.value})`,s.nodes)]}),e.functional("nth-last-of-type",(s,h)=>{if(!h.value||h.modifier||h.value.kind==="named"&&!F(h.value.value))return null;s.nodes=[X(`&:nth-last-of-type(${h.value.value})`,s.nodes)]}),e.functional("supports",(s,h)=>{if(!h.value||h.modifier)return null;let f=h.value.value;if(f===null)return null;if(/^[\w-]*\s*\(/.test(f)){let u=f.replace(/\b(and|or|not)\b/g," $1 ");s.nodes=[R("@supports",u,s.nodes)];return}f.includes(":")||(f=`${f}: var(--tw)`),(f[0]!=="("||f[f.length-1]!==")")&&(f=`(${f})`),s.nodes=[R("@supports",f,s.nodes)]},{compounds:1}),t("motion-safe",["@media (prefers-reduced-motion: no-preference)"]),t("motion-reduce",["@media (prefers-reduced-motion: reduce)"]),t("contrast-more",["@media (prefers-contrast: more)"]),t("contrast-less",["@media (prefers-contrast: less)"]);{let s=function(h,f,u,g){if(h===f)return 0;let p=g.get(h);if(p===null)return u==="asc"?-1:1;let E=g.get(f);return E===null?u==="asc"?1:-1:jj(p,E,u)};var c=s;{let h=A.namespace("--breakpoint"),f=new U(u=>{switch(u.kind){case"static":return A.resolveValue(u.root,["--breakpoint"])??null;case"functional":{if(!u.value||u.modifier)return null;let g=null;return u.value.kind==="arbitrary"?g=u.value.value:u.value.kind==="named"&&(g=A.resolveValue(u.value.value,["--breakpoint"])),!g||g.includes("var(")?null:g}case"arbitrary":case"compound":return null}});e.group(()=>{e.functional("max",(u,g)=>{if(g.modifier)return null;let p=f.get(g);if(p===null)return null;u.nodes=[R("@media",`(width < ${p})`,u.nodes)]},{compounds:1})},(u,g)=>s(u,g,"desc",f)),e.suggest("max",()=>Array.from(h.keys()).filter(u=>u!==null)),e.group(()=>{for(let[u,g]of A.namespace("--breakpoint"))u!==null&&e.static(u,p=>{p.nodes=[R("@media",`(width >= ${g})`,p.nodes)]},{compounds:1});e.functional("min",(u,g)=>{if(g.modifier)return null;let p=f.get(g);if(p===null)return null;u.nodes=[R("@media",`(width >= ${p})`,u.nodes)]},{compounds:1})},(u,g)=>s(u,g,"asc",f)),e.suggest("min",()=>Array.from(h.keys()).filter(u=>u!==null))}{let h=A.namespace("--container"),f=new U(u=>{switch(u.kind){case"functional":{if(u.value===null)return null;let g=null;return u.value.kind==="arbitrary"?g=u.value.value:u.value.kind==="named"&&(g=A.resolveValue(u.value.value,["--container"])),!g||g.includes("var(")?null:g}case"static":case"arbitrary":case"compound":return null}});e.group(()=>{e.functional("@max",(u,g)=>{let p=f.get(g);if(p===null)return null;u.nodes=[R("@container",g.modifier?`${g.modifier.value} (width < ${p})`:`(width < ${p})`,u.nodes)]},{compounds:1})},(u,g)=>s(u,g,"desc",f)),e.suggest("@max",()=>Array.from(h.keys()).filter(u=>u!==null)),e.group(()=>{e.functional("@",(u,g)=>{let p=f.get(g);if(p===null)return null;u.nodes=[R("@container",g.modifier?`${g.modifier.value} (width >= ${p})`:`(width >= ${p})`,u.nodes)]},{compounds:1}),e.functional("@min",(u,g)=>{let p=f.get(g);if(p===null)return null;u.nodes=[R("@container",g.modifier?`${g.modifier.value} (width >= ${p})`:`(width >= ${p})`,u.nodes)]},{compounds:1})},(u,g)=>s(u,g,"asc",f)),e.suggest("@min",()=>Array.from(h.keys()).filter(u=>u!==null)),e.suggest("@",()=>Array.from(h.keys()).filter(u=>u!==null))}}return t("portrait",["@media (orientation: portrait)"]),t("landscape",["@media (orientation: landscape)"]),t("ltr",['&:where(:dir(ltr), [dir="ltr"], [dir="ltr"] *)']),t("rtl",['&:where(:dir(rtl), [dir="rtl"], [dir="rtl"] *)']),t("dark",["@media (prefers-color-scheme: dark)"]),t("starting",["@starting-style"]),t("print",["@media print"]),t("forced-colors",["@media (forced-colors: active)"]),t("inverted-colors",["@media (inverted-colors: inverted)"]),t("pointer-none",["@media (pointer: none)"]),t("pointer-coarse",["@media (pointer: coarse)"]),t("pointer-fine",["@media (pointer: fine)"]),t("any-pointer-none",["@media (any-pointer: none)"]),t("any-pointer-coarse",["@media (any-pointer: coarse)"]),t("any-pointer-fine",["@media (any-pointer: fine)"]),t("noscript",["@media (scripting: none)"]),e}function De(A){if(A.includes("=")){let[e,...t]=H(A,"="),j=t.join("=").trim();if(j[0]==="'"||j[0]==='"')return A;if(j.length>1){let r=j[j.length-1];if(j[j.length-2]===" "&&(r==="i"||r==="I"||r==="s"||r==="S"))return`${e}="${j.slice(0,-2)}" ${r}`}return`${e}="${j}"`}return A}function mt(A,e){z(A,(t,{replaceWith:j})=>{if(t.kind==="at-rule"&&t.name==="@slot")j(e);else if(t.kind==="at-rule"&&(t.name==="@keyframes"||t.name==="@property"))return Object.assign(t,S([R(t.name,t.params,t.nodes)])),1})}function aa(A){let e=Nr(A),t=ra(A),j=new U(c=>Or(c,l)),r=new U(c=>Array.from(yr(c,l))),n=new U(c=>new U(s=>{let h=oa(s,l,c);try{Tj(h.map(({node:f})=>f),l)}catch{return[]}return h})),i=new U(c=>{for(let s of it(c))A.markUsedVariable(s)}),l={theme:A,utilities:e,variants:t,invalidCandidates:new Set,important:!1,candidatesToCss(c){let s=[];for(let h of c){let f=!1,{astNodes:u}=ij([h],this,{onInvalidCandidate(){f=!0}});u=yA(u,l,0),u.length===0||f?s.push(null):s.push(EA(u))}return s},getClassOrder(c){return ea(this,c)},getClassList(){return Qr(this)},getVariants(){return ja(this)},parseCandidate(c){return r.get(c)},parseVariant(c){return j.get(c)},compileAstNodes(c,s=1){return n.get(s).get(c)},printCandidate(c){return Kr(l,c)},printVariant(c){return Dj(c)},getVariantOrder(){let c=Array.from(j.values());c.sort((u,g)=>this.variants.compare(u,g));let s=new Map,h,f=0;for(let u of c)u!==null&&(h!==void 0&&this.variants.compare(h,u)!==0&&f++,s.set(u,f),h=u);return s},resolveThemeValue(c,s=!0){let h=c.lastIndexOf("/"),f=null;h!==-1&&(f=c.slice(h+1).trim(),c=c.slice(0,h).trim());let u=A.resolve(null,[c],s?1:0)??void 0;return f&&u?AA(u,f):u},trackUsedVariables(c){i.get(c)}};return l}var Me=["container-type","pointer-events","visibility","position","inset","inset-inline","inset-block","inset-inline-start","inset-inline-end","top","right","bottom","left","isolation","z-index","order","grid-column","grid-column-start","grid-column-end","grid-row","grid-row-start","grid-row-end","float","clear","--tw-container-component","margin","margin-inline","margin-block","margin-inline-start","margin-inline-end","margin-top","margin-right","margin-bottom","margin-left","box-sizing","display","field-sizing","aspect-ratio","height","max-height","min-height","width","max-width","min-width","flex","flex-shrink","flex-grow","flex-basis","table-layout","caption-side","border-collapse","border-spacing","transform-origin","translate","--tw-translate-x","--tw-translate-y","--tw-translate-z","scale","--tw-scale-x","--tw-scale-y","--tw-scale-z","rotate","--tw-rotate-x","--tw-rotate-y","--tw-rotate-z","--tw-skew-x","--tw-skew-y","transform","animation","cursor","touch-action","--tw-pan-x","--tw-pan-y","--tw-pinch-zoom","resize","scroll-snap-type","--tw-scroll-snap-strictness","scroll-snap-align","scroll-snap-stop","scroll-margin","scroll-margin-inline","scroll-margin-block","scroll-margin-inline-start","scroll-margin-inline-end","scroll-margin-top","scroll-margin-right","scroll-margin-bottom","scroll-margin-left","scroll-padding","scroll-padding-inline","scroll-padding-block","scroll-padding-inline-start","scroll-padding-inline-end","scroll-padding-top","scroll-padding-right","scroll-padding-bottom","scroll-padding-left","list-style-position","list-style-type","list-style-image","appearance","columns","break-before","break-inside","break-after","grid-auto-columns","grid-auto-flow","grid-auto-rows","grid-template-columns","grid-template-rows","flex-direction","flex-wrap","place-content","place-items","align-content","align-items","justify-content","justify-items","gap","column-gap","row-gap","--tw-space-x-reverse","--tw-space-y-reverse","divide-x-width","divide-y-width","--tw-divide-y-reverse","divide-style","divide-color","place-self","align-self","justify-self","overflow","overflow-x","overflow-y","overscroll-behavior","overscroll-behavior-x","overscroll-behavior-y","scroll-behavior","border-radius","border-start-radius","border-end-radius","border-top-radius","border-right-radius","border-bottom-radius","border-left-radius","border-start-start-radius","border-start-end-radius","border-end-end-radius","border-end-start-radius","border-top-left-radius","border-top-right-radius","border-bottom-right-radius","border-bottom-left-radius","border-width","border-inline-width","border-block-width","border-inline-start-width","border-inline-end-width","border-top-width","border-right-width","border-bottom-width","border-left-width","border-style","border-inline-style","border-block-style","border-inline-start-style","border-inline-end-style","border-top-style","border-right-style","border-bottom-style","border-left-style","border-color","border-inline-color","border-block-color","border-inline-start-color","border-inline-end-color","border-top-color","border-right-color","border-bottom-color","border-left-color","background-color","background-image","--tw-gradient-position","--tw-gradient-stops","--tw-gradient-via-stops","--tw-gradient-from","--tw-gradient-from-position","--tw-gradient-via","--tw-gradient-via-position","--tw-gradient-to","--tw-gradient-to-position","mask-image","--tw-mask-top","--tw-mask-top-from-color","--tw-mask-top-from-position","--tw-mask-top-to-color","--tw-mask-top-to-position","--tw-mask-right","--tw-mask-right-from-color","--tw-mask-right-from-position","--tw-mask-right-to-color","--tw-mask-right-to-position","--tw-mask-bottom","--tw-mask-bottom-from-color","--tw-mask-bottom-from-position","--tw-mask-bottom-to-color","--tw-mask-bottom-to-position","--tw-mask-left","--tw-mask-left-from-color","--tw-mask-left-from-position","--tw-mask-left-to-color","--tw-mask-left-to-position","--tw-mask-linear","--tw-mask-linear-position","--tw-mask-linear-from-color","--tw-mask-linear-from-position","--tw-mask-linear-to-color","--tw-mask-linear-to-position","--tw-mask-radial","--tw-mask-radial-shape","--tw-mask-radial-size","--tw-mask-radial-position","--tw-mask-radial-from-color","--tw-mask-radial-from-position","--tw-mask-radial-to-color","--tw-mask-radial-to-position","--tw-mask-conic","--tw-mask-conic-position","--tw-mask-conic-from-color","--tw-mask-conic-from-position","--tw-mask-conic-to-color","--tw-mask-conic-to-position","box-decoration-break","background-size","background-attachment","background-clip","background-position","background-repeat","background-origin","mask-composite","mask-mode","mask-type","mask-size","mask-clip","mask-position","mask-repeat","mask-origin","fill","stroke","stroke-width","object-fit","object-position","padding","padding-inline","padding-block","padding-inline-start","padding-inline-end","padding-top","padding-right","padding-bottom","padding-left","text-align","text-indent","vertical-align","font-family","font-size","line-height","font-weight","letter-spacing","text-wrap","overflow-wrap","word-break","text-overflow","hyphens","white-space","color","text-transform","font-style","font-stretch","font-variant-numeric","text-decoration-line","text-decoration-color","text-decoration-style","text-decoration-thickness","text-underline-offset","-webkit-font-smoothing","placeholder-color","caret-color","accent-color","color-scheme","opacity","background-blend-mode","mix-blend-mode","box-shadow","--tw-shadow","--tw-shadow-color","--tw-ring-shadow","--tw-ring-color","--tw-inset-shadow","--tw-inset-shadow-color","--tw-inset-ring-shadow","--tw-inset-ring-color","--tw-ring-offset-width","--tw-ring-offset-color","outline","outline-width","outline-offset","outline-color","--tw-blur","--tw-brightness","--tw-contrast","--tw-drop-shadow","--tw-grayscale","--tw-hue-rotate","--tw-invert","--tw-saturate","--tw-sepia","filter","--tw-backdrop-blur","--tw-backdrop-brightness","--tw-backdrop-contrast","--tw-backdrop-grayscale","--tw-backdrop-hue-rotate","--tw-backdrop-invert","--tw-backdrop-opacity","--tw-backdrop-saturate","--tw-backdrop-sepia","backdrop-filter","transition-property","transition-behavior","transition-delay","transition-duration","transition-timing-function","will-change","contain","content","forced-color-adjust"];function ij(A,e,{onInvalidCandidate:t,respectImportant:j}={}){let r=new Map,n=[],i=new Map;for(let s of A){if(e.invalidCandidates.has(s)){t?.(s);continue}let h=e.parseCandidate(s);if(h.length===0){t?.(s);continue}i.set(s,h)}let l=0;(j??!0)&&(l|=1);let c=e.getVariantOrder();for(let[s,h]of i){let f=!1;for(let u of h){let g=e.compileAstNodes(u,l);if(g.length!==0){f=!0;for(let{node:p,propertySort:E}of g){let x=0n;for(let I of u.variants)x|=1n<<BigInt(c.get(I));r.set(p,{properties:E,variants:x,candidate:s}),n.push(p)}}}f||t?.(s)}return n.sort((s,h)=>{let f=r.get(s),u=r.get(h);if(f.variants-u.variants!==0n)return Number(f.variants-u.variants);let g=0;for(;g<f.properties.order.length&&g<u.properties.order.length&&f.properties.order[g]===u.properties.order[g];)g+=1;return(f.properties.order[g]??1/0)-(u.properties.order[g]??1/0)||u.properties.count-f.properties.count||ht(f.candidate,u.candidate)}),{astNodes:n,nodeSorting:r}}function oa(A,e,t){let j=ia(A,e);if(j.length===0)return[];let r=e.important&&!!(t&1),n=[],i=`.${oj(A.raw)}`;for(let l of j){let c=ca(l);(A.important||r)&&pt(l);let s={kind:"rule",selector:i,nodes:l};for(let h of A.variants)if(cj(s,h,e.variants)===null)return[];n.push({node:s,propertySort:c})}return n}function cj(A,e,t,j=0){if(e.kind==="arbitrary"){if(e.relative&&j===0)return null;A.nodes=[Z(e.selector,A.nodes)];return}let{applyFn:r}=t.get(e.root);if(e.kind==="compound"){let n=R("@slot");if(cj(n,e.variant,t,j+1)===null||e.root==="not"&&n.nodes.length>1)return null;for(let i of n.nodes)if(i.kind!=="rule"&&i.kind!=="at-rule"||r(i,e)===null)return null;z(n.nodes,i=>{if((i.kind==="rule"||i.kind==="at-rule")&&i.nodes.length<=0)return i.nodes=A.nodes,1}),A.nodes=n.nodes;return}if(r(A,e)===null)return null}function Te(A){let e=A.options?.types??[];return e.length>1&&e.includes("any")}function ia(A,e){if(A.kind==="arbitrary"){let i=A.value;return A.modifier&&(i=Y(i,A.modifier,e.theme)),i===null?[]:[[o(A.property,i)]]}let t=e.utilities.get(A.root)??[],j=[],r=t.filter(i=>!Te(i));for(let i of r){if(i.kind!==A.kind)continue;let l=i.compileFn(A);if(l!==void 0){if(l===null)return j;j.push(l)}}if(j.length>0)return j;let n=t.filter(i=>Te(i));for(let i of n){if(i.kind!==A.kind)continue;let l=i.compileFn(A);if(l!==void 0){if(l===null)return j;j.push(l)}}return j}function pt(A){for(let e of A)e.kind!=="at-root"&&(e.kind==="declaration"?e.important=!0:(e.kind==="rule"||e.kind==="at-rule")&&pt(e.nodes))}function ca(A){let e=new Set,t=0,j=A.slice(),r=!1;for(;j.length>0;){let n=j.shift();if(n.kind==="declaration"){if(n.value===void 0||(t++,r))continue;if(n.property==="--tw-sort"){let l=Me.indexOf(n.value??"");if(l!==-1){e.add(l),r=!0;continue}}let i=Me.indexOf(n.property);i!==-1&&e.add(i)}else if(n.kind==="rule"||n.kind==="at-rule")for(let i of n.nodes)j.push(i)}return{order:Array.from(e).sort((n,i)=>n-i),count:t}}function Fj(A,e){let t=0,j=Z("&",A),r=new Set,n=new U(()=>new Set),i=new U(()=>new Set);z([j],(f,{parent:u,path:g})=>{if(f.kind==="at-rule"){if(f.name==="@keyframes")return z(f.nodes,p=>{if(p.kind==="at-rule"&&p.name==="@apply")throw new Error("You cannot use `@apply` inside `@keyframes`.")}),1;if(f.name==="@utility"){let p=f.params.replace(/-\*$/,"");i.get(p).add(f),z(f.nodes,E=>{if(!(E.kind!=="at-rule"||E.name!=="@apply")){r.add(f);for(let x of He(E,e))n.get(f).add(x)}});return}if(f.name==="@apply"){if(u===null)return;t|=1,r.add(u);for(let p of He(f,e))for(let E of g)E!==f&&r.has(E)&&n.get(E).add(p)}}});let l=new Set,c=[],s=new Set;function h(f,u=[]){if(!l.has(f)){if(s.has(f)){let g=u[(u.indexOf(f)+1)%u.length];throw f.kind==="at-rule"&&f.name==="@utility"&&g.kind==="at-rule"&&g.name==="@utility"&&z(f.nodes,p=>{if(p.kind!=="at-rule"||p.name!=="@apply")return;let E=p.params.split(/\s+/g);for(let x of E)for(let I of e.parseCandidate(x))switch(I.kind){case"arbitrary":break;case"static":case"functional":if(g.params.replace(/-\*$/,"")===I.root)throw new Error(`You cannot \`@apply\` the \`${x}\` utility here because it creates a circular dependency.`);break;default:}}),new Error(`Circular dependency detected:

${EA([f])}
Relies on:

${EA([g])}`)}s.add(f);for(let g of n.get(f))for(let p of i.get(g))u.push(f),h(p,u),u.pop();l.add(f),s.delete(f),c.push(f)}}for(let f of r)h(f);for(let f of c)"nodes"in f&&z(f.nodes,(u,{replaceWith:g})=>{if(u.kind!=="at-rule"||u.name!=="@apply")return;let p=u.params.split(/(\s+)/g),E={},x=0;for(let[I,b]of p.entries())I%2===0&&(E[b]=x),x+=b.length;{let I=Object.keys(E),b=ij(I,e,{respectImportant:!1,onInvalidCandidate:K=>{if(e.theme.prefix&&!K.startsWith(e.theme.prefix))throw new Error(`Cannot apply unprefixed utility class \`${K}\`. Did you mean \`${e.theme.prefix}:${K}\`?`);if(e.invalidCandidates.has(K))throw new Error(`Cannot apply utility class \`${K}\` because it has been explicitly disabled: https://tailwindcss.com/docs/detecting-classes-in-source-files#explicitly-excluding-classes`);let D=H(K,":");if(D.length>1){let O=D.pop();if(e.candidatesToCss([O])[0]){let N=e.candidatesToCss(D.map(L=>`${L}:[--tw-variant-check:1]`)),M=D.filter((L,J)=>N[J]===null);if(M.length>0){if(M.length===1)throw new Error(`Cannot apply utility class \`${K}\` because the ${M.map(L=>`\`${L}\``)} variant does not exist.`);{let L=new Intl.ListFormat("en",{style:"long",type:"conjunction"});throw new Error(`Cannot apply utility class \`${K}\` because the ${L.format(M.map(J=>`\`${J}\``))} variants do not exist.`)}}}}throw e.theme.size===0?new Error(`Cannot apply unknown utility class \`${K}\`. Are you using CSS modules or similar and missing \`@reference\`? https://tailwindcss.com/docs/functions-and-directives#reference-directive`):new Error(`Cannot apply unknown utility class \`${K}\``)}}),G=u.src,w=b.astNodes.map(K=>{let D=b.nodeSorting.get(K)?.candidate,O=D?E[D]:void 0;if(K=structuredClone(K),!G||!D||O===void 0)return z([K],M=>{M.src=G}),K;let N=[G[0],G[1],G[2]];return N[1]+=7+O,N[2]=N[1]+D.length,z([K],M=>{M.src=N}),K}),P=[];for(let K of w)if(K.kind==="rule")for(let D of K.nodes)P.push(D);else P.push(K);g(P)}});return t}function*He(A,e){for(let t of A.params.split(/\s+/g))for(let j of e.parseCandidate(t))switch(j.kind){case"arbitrary":break;case"static":case"functional":yield j.root;break;default:}}async function gt(A,e,t,j=0,r=!1){let n=0,i=[];return z(A,(l,{replaceWith:c})=>{if(l.kind==="at-rule"&&(l.name==="@import"||l.name==="@reference")){let s=na(W(l.params));if(s===null)return;l.name==="@reference"&&(s.media="reference"),n|=2;let{uri:h,layer:f,media:u,supports:g}=s;if(h.startsWith("data:")||h.startsWith("http://")||h.startsWith("https://"))return;let p=kA({},[]);return i.push((async()=>{if(j>100)throw new Error(`Exceeded maximum recursion depth while resolving \`${h}\` in \`${e}\`)`);let E=await t(h,e),x=Pj(E.content,{from:r?E.path:void 0});await gt(x,E.base,t,j+1,r),p.nodes=sa(l,[kA({base:E.base},x)],f,u,g)})()),c(p),1}}),i.length>0&&await Promise.all(i),n}function na(A){let e,t=null,j=null,r=null;for(let n=0;n<A.length;n++){let i=A[n];if(i.kind!=="separator"){if(i.kind==="word"&&!e){if(!i.value||i.value[0]!=='"'&&i.value[0]!=="'")return null;e=i.value.slice(1,-1);continue}if(i.kind==="function"&&i.value.toLowerCase()==="url"||!e)return null;if((i.kind==="word"||i.kind==="function")&&i.value.toLowerCase()==="layer"){if(t)return null;if(r)throw new Error("`layer(\u2026)` in an `@import` should come before any other functions or conditions");"nodes"in i?t=jA(i.nodes):t="";continue}if(i.kind==="function"&&i.value.toLowerCase()==="supports"){if(r)return null;r=jA(i.nodes);continue}j=jA(A.slice(n));break}}return e?{uri:e,layer:t,media:j,supports:r}:null}function sa(A,e,t,j,r){let n=e;if(t!==null){let i=R("@layer",t,n);i.src=A.src,n=[i]}if(j!==null){let i=R("@media",j,n);i.src=A.src,n=[i]}if(r!==null){let i=R("@supports",r[0]==="("?r:`(${r})`,n);i.src=A.src,n=[i]}return n}function BA(A,e=null){return Array.isArray(A)&&A.length===2&&typeof A[1]=="object"&&typeof A[1]!==null?e?A[1][e]??null:A[0]:Array.isArray(A)&&e===null?A.join(", "):typeof A=="string"&&e===null?A:null}function la(A,{theme:e},t){for(let j of t){let r=wj([j]);r&&A.theme.clearNamespace(`--${r}`,4)}for(let[j,r]of da(e)){if(typeof r!="string"&&typeof r!="number")continue;if(typeof r=="string"&&(r=r.replace(/<alpha-value>/g,"1")),j[0]==="opacity"&&(typeof r=="number"||typeof r=="string")){let i=typeof r=="string"?parseFloat(r):r;i>=0&&i<=1&&(r=i*100+"%")}let n=wj(j);n&&A.theme.add(`--${n}`,""+r,7)}if(Object.hasOwn(e,"fontFamily")){let j=5;{let r=BA(e.fontFamily.sans);r&&A.theme.hasDefault("--font-sans")&&(A.theme.add("--default-font-family",r,j),A.theme.add("--default-font-feature-settings",BA(e.fontFamily.sans,"fontFeatureSettings")??"normal",j),A.theme.add("--default-font-variation-settings",BA(e.fontFamily.sans,"fontVariationSettings")??"normal",j))}{let r=BA(e.fontFamily.mono);r&&A.theme.hasDefault("--font-mono")&&(A.theme.add("--default-mono-font-family",r,j),A.theme.add("--default-mono-font-feature-settings",BA(e.fontFamily.mono,"fontFeatureSettings")??"normal",j),A.theme.add("--default-mono-font-variation-settings",BA(e.fontFamily.mono,"fontVariationSettings")??"normal",j))}}return e}function da(A){let e=[];return kt(A,[],(t,j)=>{if(ha(t))return e.push([j,t]),1;if(ua(t)){e.push([j,t[0]]);for(let r of Reflect.ownKeys(t[1]))e.push([[...j,`-${r}`],t[1][r]]);return 1}if(Array.isArray(t)&&t.every(r=>typeof r=="string"))return j[0]==="fontSize"?(e.push([j,t[0]]),t.length>=2&&e.push([[...j,"-line-height"],t[1]])):e.push([j,t.join(", ")]),1}),e}var fa=/^[a-zA-Z0-9-_%/\.]+$/;function wj(A){if(A[0]==="container")return null;A=structuredClone(A),A[0]==="animation"&&(A[0]="animate"),A[0]==="aspectRatio"&&(A[0]="aspect"),A[0]==="borderRadius"&&(A[0]="radius"),A[0]==="boxShadow"&&(A[0]="shadow"),A[0]==="colors"&&(A[0]="color"),A[0]==="containers"&&(A[0]="container"),A[0]==="fontFamily"&&(A[0]="font"),A[0]==="fontSize"&&(A[0]="text"),A[0]==="letterSpacing"&&(A[0]="tracking"),A[0]==="lineHeight"&&(A[0]="leading"),A[0]==="maxWidth"&&(A[0]="container"),A[0]==="screens"&&(A[0]="breakpoint"),A[0]==="transitionTimingFunction"&&(A[0]="ease");for(let e of A)if(!fa.test(e))return null;return A.map((e,t,j)=>e==="1"&&t!==j.length-1?"":e).map(e=>e.replaceAll(".","_").replace(/([a-z])([A-Z])/g,(t,j,r)=>`${j}-${r.toLowerCase()}`)).filter((e,t)=>e!=="DEFAULT"||t!==A.length-1).join("-")}function ha(A){return typeof A=="number"||typeof A=="string"}function ua(A){if(!Array.isArray(A)||A.length!==2||typeof A[0]!="string"&&typeof A[0]!="number"||A[1]===void 0||A[1]===null||typeof A[1]!="object")return!1;for(let e of Reflect.ownKeys(A[1]))if(typeof e!="string"||typeof A[1][e]!="string"&&typeof A[1][e]!="number")return!1;return!0}function kt(A,e=[],t){for(let j of Reflect.ownKeys(A)){let r=A[j];if(r==null)continue;let n=[...e,j],i=t(r,n)??0;if(i!==1&&(i===2||!(!Array.isArray(r)&&typeof r!="object")&&kt(r,n,t)===2))return 2}}function bt(A){let e=[];for(let t of H(A,".")){if(!t.includes("[")){e.push(t);continue}let j=0;for(;;){let r=t.indexOf("[",j),n=t.indexOf("]",r);if(r===-1||n===-1)break;r>j&&e.push(t.slice(j,r)),e.push(t.slice(r+1,n)),j=n+1}j<=t.length-1&&e.push(t.slice(j))}return e}function OA(A){if(Object.prototype.toString.call(A)!=="[object Object]")return!1;let e=Object.getPrototypeOf(A);return e===null||Object.getPrototypeOf(e)===null}function Hj(A,e,t,j=[]){for(let r of e)if(r!=null)for(let n of Reflect.ownKeys(r)){j.push(n);let i=t(A[n],r[n],j);i!==void 0?A[n]=i:!OA(A[n])||!OA(r[n])?A[n]=r[n]:A[n]=Hj({},[A[n],r[n]],t,j),j.pop()}return A}function Et(A,e,t){return function(j,r){let n=j.lastIndexOf("/"),i=null;n!==-1&&(i=j.slice(n+1).trim(),j=j.slice(0,n).trim());let l=(()=>{let c=bt(j),[s,h]=ma(A.theme,c),f=t(Le(e()??{},c)??null);if(typeof f=="string"&&(f=f.replace("<alpha-value>","1")),typeof s!="object")return typeof h!="object"&&h&4?f??s:s;if(f!==null&&typeof f=="object"&&!Array.isArray(f)){let u=Hj({},[f],(g,p)=>p);if(s===null&&Object.hasOwn(f,"__CSS_VALUES__")){let g={};for(let p in f.__CSS_VALUES__)g[p]=f[p],delete u[p];s=g}for(let g in s)g!=="__CSS_VALUES__"&&(f?.__CSS_VALUES__?.[g]&4&&Le(u,g.split("-"))!==void 0||(u[Aj(g)]=s[g]));return u}if(Array.isArray(s)&&Array.isArray(h)&&Array.isArray(f)){let u=s[0],g=s[1];h[0]&4&&(u=f[0]??u);for(let p of Object.keys(g))h[1][p]&4&&(g[p]=f[1][p]??g[p]);return[u,g]}return s??f})();return i&&typeof l=="string"&&(l=AA(l,i)),l??r}}function ma(A,e){if(e.length===1&&e[0].startsWith("--"))return[A.get([e[0]]),A.getOptions(e[0])];let t=wj(e),j=new Map,r=new U(()=>new Map),n=A.namespace(`--${t}`);if(n.size===0)return[null,0];let i=new Map;for(let[h,f]of n){if(!h||!h.includes("--")){j.set(h,f),i.set(h,A.getOptions(h?`--${t}-${h}`:`--${t}`));continue}let u=h.indexOf("--"),g=h.slice(0,u),p=h.slice(u+2);p=p.replace(/-([a-z])/g,(E,x)=>x.toUpperCase()),r.get(g===""?null:g).set(p,[f,A.getOptions(`--${t}${h}`)])}let l=A.getOptions(`--${t}`);for(let[h,f]of r){let u=j.get(h);if(typeof u!="string")continue;let g={},p={};for(let[E,[x,I]]of f)g[E]=x,p[E]=I;j.set(h,[u,g]),i.set(h,[l,p])}let c={},s={};for(let[h,f]of j)Se(c,[h??"DEFAULT"],f);for(let[h,f]of i)Se(s,[h??"DEFAULT"],f);return e[e.length-1]==="DEFAULT"?[c?.DEFAULT??null,s.DEFAULT??0]:"DEFAULT"in c&&Object.keys(c).length===1?[c.DEFAULT,s.DEFAULT??0]:(c.__CSS_VALUES__=s,[c,s])}function Le(A,e){for(let t=0;t<e.length;++t){let j=e[t];if(A?.[j]===void 0){if(e[t+1]===void 0)return;e[t+1]=`${j}-${e[t+1]}`;continue}A=A[j]}return A}function Se(A,e,t){for(let j of e.slice(0,-1))A[j]===void 0&&(A[j]={}),A=A[j];A[e[e.length-1]]=t}function pa(A){return{kind:"combinator",value:A}}function ga(A,e){return{kind:"function",value:A,nodes:e}}function GA(A){return{kind:"selector",value:A}}function ka(A){return{kind:"separator",value:A}}function ba(A){return{kind:"value",value:A}}function rj(A,e,t=null){for(let j=0;j<A.length;j++){let r=A[j],n=!1,i=0,l=e(r,{parent:t,replaceWith(c){n||(n=!0,Array.isArray(c)?c.length===0?(A.splice(j,1),i=0):c.length===1?(A[j]=c[0],i=1):(A.splice(j,1,...c),i=c.length):(A[j]=c,i=1))}})??0;if(n){l===0?j--:j+=i-1;continue}if(l===2||l!==1&&r.kind==="function"&&rj(r.nodes,e,r)===2)return 2}}function aj(A){let e="";for(let t of A)switch(t.kind){case"combinator":case"selector":case"separator":case"value":{e+=t.value;break}case"function":e+=t.value+"("+aj(t.nodes)+")"}return e}var Ce=92,Ea=93,ze=41,Ba=58,Ne=44,xa=34,_a=46,Re=62,Xe=10,va=35,Ve=91,Je=40,Ue=43,$a=39,We=32,Ye=9,Ze=126;function yj(A){A=A.replaceAll(`\r
`,`
`);let e=[],t=[],j=null,r="",n;for(let i=0;i<A.length;i++){let l=A.charCodeAt(i);switch(l){case Ne:case Re:case Xe:case We:case Ue:case Ye:case Ze:{if(r.length>0){let u=GA(r);j?j.nodes.push(u):e.push(u),r=""}let c=i,s=i+1;for(;s<A.length&&(n=A.charCodeAt(s),!(n!==Ne&&n!==Re&&n!==Xe&&n!==We&&n!==Ue&&n!==Ye&&n!==Ze));s++);i=s-1;let h=A.slice(c,s),f=h.trim()===","?ka(h):pa(h);j?j.nodes.push(f):e.push(f);break}case Je:{let c=ga(r,[]);if(r="",c.value!==":not"&&c.value!==":where"&&c.value!==":has"&&c.value!==":is"){let s=i+1,h=0;for(let u=i+1;u<A.length;u++){if(n=A.charCodeAt(u),n===Je){h++;continue}if(n===ze){if(h===0){i=u;break}h--}}let f=i;c.nodes.push(ba(A.slice(s,f))),r="",i=f,j?j.nodes.push(c):e.push(c);break}j?j.nodes.push(c):e.push(c),t.push(c),j=c;break}case ze:{let c=t.pop();if(r.length>0){let s=GA(r);c.nodes.push(s),r=""}t.length>0?j=t[t.length-1]:j=null;break}case _a:case Ba:case va:{if(r.length>0){let c=GA(r);j?j.nodes.push(c):e.push(c)}r=String.fromCharCode(l);break}case Ve:{if(r.length>0){let h=GA(r);j?j.nodes.push(h):e.push(h)}r="";let c=i,s=0;for(let h=i+1;h<A.length;h++){if(n=A.charCodeAt(h),n===Ve){s++;continue}if(n===Ea){if(s===0){i=h;break}s--}}r+=A.slice(c,i+1);break}case $a:case xa:{let c=i;for(let s=i+1;s<A.length;s++)if(n=A.charCodeAt(s),n===Ce)s+=1;else if(n===l){i=s;break}r+=A.slice(c,i+1);break}case Ce:{let c=A.charCodeAt(i+1);r+=String.fromCharCode(l)+String.fromCharCode(c),i+=1;break}default:r+=String.fromCharCode(l)}}return r.length>0&&e.push(GA(r)),e}var Qe=/^[a-z@][a-zA-Z0-9/%._-]*$/;function At({designSystem:A,ast:e,resolvedConfig:t,featuresRef:j,referenceMode:r,src:n}){let i={addBase(l){if(r)return;let c=fA(l);j.current|=Tj(c,A);let s=R("@layer","base",c);z([s],h=>{h.src=n}),e.push(s)},addVariant(l,c){if(!ut.test(l))throw new Error(`\`addVariant('${l}')\` defines an invalid variant name. Variants should only contain alphanumeric, dashes or underscore characters.`);if(typeof c=="string"){if(c.includes(":merge("))return}else if(Array.isArray(c)){if(c.some(h=>h.includes(":merge(")))return}else if(typeof c=="object"){let h=function(f,u){return Object.entries(f).some(([g,p])=>g.includes(u)||typeof p=="object"&&h(p,u))};var s=h;if(h(c,":merge("))return}typeof c=="string"||Array.isArray(c)?A.variants.static(l,h=>{h.nodes=jt(c,h.nodes)},{compounds:KA(typeof c=="string"?[c]:c)}):typeof c=="object"&&A.variants.fromAst(l,fA(c))},matchVariant(l,c,s){function h(u,g,p){let E=c(u,{modifier:g?.value??null});return jt(E,p)}try{let u=c("a",{modifier:null});if(typeof u=="string"&&u.includes(":merge(")||Array.isArray(u)&&u.some(g=>g.includes(":merge(")))return}catch{}let f=Object.keys(s?.values??{});A.variants.group(()=>{A.variants.functional(l,(u,g)=>{if(!g.value){if(s?.values&&"DEFAULT"in s.values){u.nodes=h(s.values.DEFAULT,g.modifier,u.nodes);return}return null}if(g.value.kind==="arbitrary")u.nodes=h(g.value.value,g.modifier,u.nodes);else if(g.value.kind==="named"&&s?.values){let p=s.values[g.value.value];if(typeof p!="string")return;u.nodes=h(p,g.modifier,u.nodes)}})},(u,g)=>{if(u.kind!=="functional"||g.kind!=="functional")return 0;let p=u.value?u.value.value:"DEFAULT",E=g.value?g.value.value:"DEFAULT",x=s?.values?.[p]??p,I=s?.values?.[E]??E;if(s&&typeof s.sort=="function")return s.sort({value:x,modifier:u.modifier?.value??null},{value:I,modifier:g.modifier?.value??null});let b=f.indexOf(p),G=f.indexOf(E);return b=b===-1?f.length:b,G=G===-1?f.length:G,b!==G?b-G:x<I?-1:1})},addUtilities(l){l=Array.isArray(l)?l:[l];let c=l.flatMap(h=>Object.entries(h));c=c.flatMap(([h,f])=>H(h,",").map(u=>[u.trim(),f]));let s=new U(()=>[]);for(let[h,f]of c){if(h.startsWith("@keyframes ")){if(!r){let p=Z(h,fA(f));z([p],E=>{E.src=n}),e.push(p)}continue}let u=yj(h),g=!1;if(rj(u,p=>{if(p.kind==="selector"&&p.value[0]==="."&&Qe.test(p.value.slice(1))){let E=p.value;p.value="&";let x=aj(u),I=E.slice(1),b=x==="&"?fA(f):[Z(x,fA(f))];s.get(I).push(...b),g=!0,p.value=E;return}if(p.kind==="function"&&p.value===":not")return 1}),!g)throw new Error(`\`addUtilities({ '${h}' : \u2026 })\` defines an invalid utility selector. Utilities must be a single class name and start with a lowercase letter, eg. \`.scrollbar-none\`.`)}for(let[h,f]of s)A.theme.prefix&&z(f,u=>{if(u.kind==="rule"){let g=yj(u.selector);rj(g,p=>{p.kind==="selector"&&p.value[0]==="."&&(p.value=`.${A.theme.prefix}\\:${p.value.slice(1)}`)}),u.selector=aj(g)}}),A.utilities.static(h,u=>{let g=structuredClone(f);return et(g,h,u.raw),j.current|=Fj(g,A),g})},matchUtilities(l,c){let s=c?.type?Array.isArray(c?.type)?c.type:[c.type]:["any"];for(let[f,u]of Object.entries(l)){let g=function({negative:p}){return E=>{if(E.value?.kind==="arbitrary"&&s.length>0&&!s.includes("any")&&(E.value.dataType&&!s.includes(E.value.dataType)||!E.value.dataType&&!V(E.value.value,s)))return;let x=s.includes("color"),I=null,b=!1;{let P=c?.values??{};x&&(P=Object.assign({inherit:"inherit",transparent:"transparent",current:"currentcolor"},P)),E.value?E.value.kind==="arbitrary"?I=E.value.value:E.value.fraction&&P[E.value.fraction]?(I=P[E.value.fraction],b=!0):P[E.value.value]?I=P[E.value.value]:P.__BARE_VALUE__&&(I=P.__BARE_VALUE__(E.value)??null,b=(E.value.fraction!==null&&I?.includes("/"))??!1):I=P.DEFAULT??null}if(I===null)return;let G;{let P=c?.modifiers??null;E.modifier?P==="any"||E.modifier.kind==="arbitrary"?G=E.modifier.value:P?.[E.modifier.value]?G=P[E.modifier.value]:x&&!Number.isNaN(Number(E.modifier.value))?G=`${E.modifier.value}%`:G=null:G=null}if(E.modifier&&G===null&&!b)return E.value?.kind==="arbitrary"?null:void 0;x&&G!==null&&(I=AA(I,G)),p&&(I=`calc(${I} * -1)`);let w=fA(u(I,{modifier:G}));return et(w,f,E.raw),j.current|=Fj(w,A),w}};var h=g;if(!Qe.test(f))throw new Error(`\`matchUtilities({ '${f}' : \u2026 })\` defines an invalid utility name. Utilities should be alphanumeric and start with a lowercase letter, eg. \`scrollbar\`.`);c?.supportsNegativeValues&&A.utilities.functional(`-${f}`,g({negative:!0}),{types:s}),A.utilities.functional(f,g({negative:!1}),{types:s}),A.utilities.suggest(f,()=>{let p=c?.values??{},E=new Set(Object.keys(p));E.delete("__BARE_VALUE__"),E.has("DEFAULT")&&(E.delete("DEFAULT"),E.add(null));let x=c?.modifiers??{},I=x==="any"?[]:Object.keys(x);return[{supportsNegative:c?.supportsNegativeValues??!1,values:Array.from(E),modifiers:I}]})}},addComponents(l,c){this.addUtilities(l,c)},matchComponents(l,c){this.matchUtilities(l,c)},theme:Et(A,()=>t.theme??{},l=>l),prefix(l){return l},config(l,c){let s=t;if(!l)return s;let h=bt(l);for(let f=0;f<h.length;++f){let u=h[f];if(s[u]===void 0)return c;s=s[u]}return s??c}};return i.addComponents=i.addComponents.bind(i),i.matchComponents=i.matchComponents.bind(i),i}function fA(A){let e=[];A=Array.isArray(A)?A:[A];let t=A.flatMap(j=>Object.entries(j));for(let[j,r]of t)if(r!=null&&r!==!1)if(typeof r!="object"){if(!j.startsWith("--")){if(r==="@slot"){e.push(Z(j,[R("@slot")]));continue}j=j.replace(/([A-Z])/g,"-$1").toLowerCase()}e.push(o(j,String(r)))}else if(Array.isArray(r))for(let n of r)typeof n=="string"?e.push(o(j,n)):e.push(Z(j,fA(n)));else e.push(Z(j,fA(r)));return e}function jt(A,e){return(typeof A=="string"?[A]:A).flatMap(t=>{if(t.trim().endsWith("}")){let j=t.replace("}","{@slot}}"),r=Pj(j);return mt(r,e),r}else return Z(t,e)})}function et(A,e,t){z(A,j=>{if(j.kind==="rule"){let r=yj(j.selector);rj(r,n=>{n.kind==="selector"&&n.value===`.${e}`&&(n.value=`.${oj(t)}`)}),j.selector=aj(r)}})}function Ia(A,e,t){for(let j of qa(e))A.theme.addKeyframes(j)}function qa(A){let e=[];if("keyframes"in A.theme)for(let[t,j]of Object.entries(A.theme.keyframes))e.push(R("@keyframes",t,fA(j)));return e}function Ga(A){return{theme:{...se,colors:({theme:e})=>e("color",{}),extend:{fontSize:({theme:e})=>({...e("text",{})}),boxShadow:({theme:e})=>({...e("shadow",{})}),animation:({theme:e})=>({...e("animate",{})}),aspectRatio:({theme:e})=>({...e("aspect",{})}),borderRadius:({theme:e})=>({...e("radius",{})}),screens:({theme:e})=>({...e("breakpoint",{})}),letterSpacing:({theme:e})=>({...e("tracking",{})}),lineHeight:({theme:e})=>({...e("leading",{})}),transitionDuration:{DEFAULT:A.get(["--default-transition-duration"])??null},transitionTimingFunction:{DEFAULT:A.get(["--default-transition-timing-function"])??null},maxWidth:({theme:e})=>({...e("container",{})})}}}}var Fa={blocklist:[],future:{},prefix:"",important:!1,darkMode:null,theme:{},plugins:[],content:{files:[]}};function tt(A,e){let t={design:A,configs:[],plugins:[],content:{files:[]},theme:{},extend:{},result:structuredClone(Fa)};for(let r of e)Oj(t,r);for(let r of t.configs)"darkMode"in r&&r.darkMode!==void 0&&(t.result.darkMode=r.darkMode??null),"prefix"in r&&r.prefix!==void 0&&(t.result.prefix=r.prefix??""),"blocklist"in r&&r.blocklist!==void 0&&(t.result.blocklist=r.blocklist??[]),"important"in r&&r.important!==void 0&&(t.result.important=r.important??!1);let j=ya(t);return{resolvedConfig:{...t.result,content:t.content,theme:t.theme,plugins:t.plugins},replacedThemeKeys:j}}function wa(A,e){if(Array.isArray(A)&&OA(A[0]))return A.concat(e);if(Array.isArray(e)&&OA(e[0])&&OA(A))return[A,...e];if(Array.isArray(e))return e}function Oj(A,{config:e,base:t,path:j,reference:r,src:n}){let i=[];for(let s of e.plugins??[])"__isOptionsFunction"in s?i.push({...s(),reference:r,src:n}):"handler"in s?i.push({...s,reference:r,src:n}):i.push({handler:s,reference:r,src:n});if(Array.isArray(e.presets)&&e.presets.length===0)throw new Error("Error in the config file/plugin/preset. An empty preset (`preset: []`) is not currently supported.");for(let s of e.presets??[])Oj(A,{path:j,base:t,config:s,reference:r,src:n});for(let s of i)A.plugins.push(s),s.config&&Oj(A,{path:j,base:t,config:s.config,reference:!!s.reference,src:s.src??n});let l=e.content??[],c=Array.isArray(l)?l:l.files;for(let s of c)A.content.files.push(typeof s=="object"?s:{base:t,pattern:s});A.configs.push(e)}function ya(A){var n;let e=new Set,t=Et(A.design,()=>A.theme,r),j=Object.assign(t,{theme:t,colors:HA});function r(i){return typeof i=="function"?i(j)??null:i??null}for(let i of A.configs){let l=i.theme??{},c=l.extend??{};for(let s in l)s!=="extend"&&e.add(s);Object.assign(A.theme,l);for(let s in c)(n=A.extend)[s]??(n[s]=[]),A.extend[s].push(c[s])}delete A.theme.extend;for(let i in A.extend){let l=[A.theme[i],...A.extend[i]];A.theme[i]=()=>{let c=l.map(r);return Hj({},c,wa)}}for(let i in A.theme)A.theme[i]=r(A.theme[i]);if(A.theme.screens&&typeof A.theme.screens=="object")for(let i of Object.keys(A.theme.screens)){let l=A.theme.screens[i];l&&typeof l=="object"&&("raw"in l||"max"in l||"min"in l&&(A.theme.screens[i]=l.min))}return e}function Oa(A,e){let t=A.theme.container||{};if(typeof t!="object"||t===null)return;let j=Ka(t,e);j.length!==0&&e.utilities.static("container",()=>structuredClone(j))}function Ka({center:A,padding:e,screens:t},j){let r=[],n=null;if(A&&r.push(o("margin-inline","auto")),(typeof e=="string"||typeof e=="object"&&e!==null&&"DEFAULT"in e)&&r.push(o("padding-inline",typeof e=="string"?e:e.DEFAULT)),typeof t=="object"&&t!==null){n=new Map;let i=Array.from(j.theme.namespace("--breakpoint").entries());if(i.sort((l,c)=>jj(l[1],c[1],"asc")),i.length>0){let[l]=i[0];r.push(R("@media",`(width >= --theme(--breakpoint-${l}))`,[o("max-width","none")]))}for(let[l,c]of Object.entries(t)){if(typeof c=="object")if("min"in c)c=c.min;else continue;n.set(l,R("@media",`(width >= ${c})`,[o("max-width",c)]))}}if(typeof e=="object"&&e!==null){let i=Object.entries(e).filter(([l])=>l!=="DEFAULT").map(([l,c])=>[l,j.theme.resolveValue(l,["--breakpoint"]),c]).filter(Boolean);i.sort((l,c)=>jj(l[1],c[1],"asc"));for(let[l,,c]of i)if(n&&n.has(l))n.get(l).nodes.push(o("padding-inline",c));else{if(n)continue;r.push(R("@media",`(width >= theme(--breakpoint-${l}))`,[o("padding-inline",c)]))}}if(n)for(let[,i]of n)r.push(i);return r}function Pa({addVariant:A,config:e}){let t=e("darkMode",null),[j,r=".dark"]=Array.isArray(t)?t:[t];if(j==="variant"){let n;if(Array.isArray(r)||typeof r=="function"?n=r:typeof r=="string"&&(n=[r]),Array.isArray(n))for(let i of n)i===".dark"?(j=!1,console.warn('When using `variant` for `darkMode`, you must provide a selector.\nExample: `darkMode: ["variant", ".your-selector &"]`')):i.includes("&")||(j=!1,console.warn('When using `variant` for `darkMode`, your selector must contain `&`.\nExample `darkMode: ["variant", ".your-selector &"]`'));r=n}j===null||(j==="selector"?A("dark",`&:where(${r}, ${r} *)`):j==="media"?A("dark","@media (prefers-color-scheme: dark)"):j==="variant"?A("dark",r):j==="class"&&A("dark",`&:is(${r} *)`))}function Da(A){for(let[e,t]of[["t","top"],["tr","top right"],["r","right"],["br","bottom right"],["b","bottom"],["bl","bottom left"],["l","left"],["tl","top left"]])A.utilities.static(`bg-gradient-to-${e}`,()=>[o("--tw-gradient-position",`to ${t} in oklab`),o("background-image","linear-gradient(var(--tw-gradient-stops))")]);A.utilities.static("bg-left-top",()=>[o("background-position","left top")]),A.utilities.static("bg-right-top",()=>[o("background-position","right top")]),A.utilities.static("bg-left-bottom",()=>[o("background-position","left bottom")]),A.utilities.static("bg-right-bottom",()=>[o("background-position","right bottom")]),A.utilities.static("object-left-top",()=>[o("object-position","left top")]),A.utilities.static("object-right-top",()=>[o("object-position","right top")]),A.utilities.static("object-left-bottom",()=>[o("object-position","left bottom")]),A.utilities.static("object-right-bottom",()=>[o("object-position","right bottom")]),A.utilities.functional("max-w-screen",e=>{if(!e.value||e.value.kind==="arbitrary")return;let t=A.theme.resolve(e.value.value,["--breakpoint"]);if(t)return[o("max-width",t)]}),A.utilities.static("overflow-ellipsis",()=>[o("text-overflow","ellipsis")]),A.utilities.static("decoration-slice",()=>[o("-webkit-box-decoration-break","slice"),o("box-decoration-break","slice")]),A.utilities.static("decoration-clone",()=>[o("-webkit-box-decoration-break","clone"),o("box-decoration-break","clone")]),A.utilities.functional("flex-shrink",e=>{if(!e.modifier){if(!e.value)return[o("flex-shrink","1")];if(e.value.kind==="arbitrary")return[o("flex-shrink",e.value.value)];if(F(e.value.value))return[o("flex-shrink",e.value.value)]}}),A.utilities.functional("flex-grow",e=>{if(!e.modifier){if(!e.value)return[o("flex-grow","1")];if(e.value.kind==="arbitrary")return[o("flex-grow",e.value.value)];if(F(e.value.value))return[o("flex-grow",e.value.value)]}}),A.utilities.static("order-none",()=>[o("order","0")])}function Ma(A,e){let t=A.theme.screens||{},j=e.variants.get("min")?.order??0,r=[];for(let[i,l]of Object.entries(t)){let c=function(g){e.variants.static(i,p=>{p.nodes=[R("@media",u,p.nodes)]},{order:g})};var n=c;let s=e.variants.get(i),h=e.theme.resolveValue(i,["--breakpoint"]);if(s&&h&&!e.theme.hasDefault(`--breakpoint-${i}`))continue;let f=!0;typeof l=="string"&&(f=!1);let u=Ta(l);f?r.push(c):c(j)}if(r.length!==0){for(let[,i]of e.variants.variants)i.order>j&&(i.order+=r.length);e.variants.compareFns=new Map(Array.from(e.variants.compareFns).map(([i,l])=>(i>j&&(i+=r.length),[i,l])));for(let[i,l]of r.entries())l(j+i+1)}}function Ta(A){return(Array.isArray(A)?A:[A]).map(e=>typeof e=="string"?{min:e}:e&&typeof e=="object"?e:null).map(e=>{if(e===null)return null;if("raw"in e)return e.raw;let t="";return e.max!==void 0&&(t+=`${e.max} >= `),t+="width",e.min!==void 0&&(t+=` >= ${e.min}`),`(${t})`}).filter(Boolean).join(", ")}function Ha(A,e){let t=A.theme.aria||{},j=A.theme.supports||{},r=A.theme.data||{};if(Object.keys(t).length>0){let n=e.variants.get("aria"),i=n?.applyFn,l=n?.compounds;e.variants.functional("aria",(c,s)=>{let h=s.value;return h&&h.kind==="named"&&h.value in t?i?.(c,{...s,value:{kind:"arbitrary",value:t[h.value]}}):i?.(c,s)},{compounds:l})}if(Object.keys(j).length>0){let n=e.variants.get("supports"),i=n?.applyFn,l=n?.compounds;e.variants.functional("supports",(c,s)=>{let h=s.value;return h&&h.kind==="named"&&h.value in j?i?.(c,{...s,value:{kind:"arbitrary",value:j[h.value]}}):i?.(c,s)},{compounds:l})}if(Object.keys(r).length>0){let n=e.variants.get("data"),i=n?.applyFn,l=n?.compounds;e.variants.functional("data",(c,s)=>{let h=s.value;return h&&h.kind==="named"&&h.value in r?i?.(c,{...s,value:{kind:"arbitrary",value:r[h.value]}}):i?.(c,s)},{compounds:l})}}var La=/^[a-z]+$/;async function Sa({designSystem:A,base:e,ast:t,loadModule:j,sources:r}){let n=0,i=[],l=[];z(t,(f,{parent:u,replaceWith:g,context:p})=>{if(f.kind==="at-rule"){if(f.name==="@plugin"){if(u!==null)throw new Error("`@plugin` cannot be nested.");let E=f.params.slice(1,-1);if(E.length===0)throw new Error("`@plugin` must have a path.");let x={};for(let I of f.nodes??[]){if(I.kind!=="declaration")throw new Error(`Unexpected \`@plugin\` option:

${EA([I])}

\`@plugin\` options must be a flat list of declarations.`);if(I.value===void 0)continue;let b=I.value,G=H(b,",").map(w=>{if(w=w.trim(),w==="null")return null;if(w==="true")return!0;if(w==="false")return!1;if(Number.isNaN(Number(w))){if(w[0]==='"'&&w[w.length-1]==='"'||w[0]==="'"&&w[w.length-1]==="'")return w.slice(1,-1);if(w[0]==="{"&&w[w.length-1]==="}")throw new Error(`Unexpected \`@plugin\` option: Value of declaration \`${EA([I]).trim()}\` is not supported.

Using an object as a plugin option is currently only supported in JavaScript configuration files.`)}else return Number(w);return w});x[I.property]=G.length===1?G[0]:G}i.push([{id:E,base:p.base,reference:!!p.reference,src:f.src},Object.keys(x).length>0?x:null]),g([]),n|=4;return}if(f.name==="@config"){if(f.nodes.length>0)throw new Error("`@config` cannot have a body.");if(u!==null)throw new Error("`@config` cannot be nested.");l.push({id:f.params.slice(1,-1),base:p.base,reference:!!p.reference,src:f.src}),g([]),n|=4;return}}}),Da(A);let c=A.resolveThemeValue;if(A.resolveThemeValue=function(f,u){return f.startsWith("--")?c(f,u):(n|=rt({designSystem:A,base:e,ast:t,sources:r,configs:[],pluginDetails:[]}),A.resolveThemeValue(f,u))},!i.length&&!l.length)return 0;let[s,h]=await Promise.all([Promise.all(l.map(async({id:f,base:u,reference:g,src:p})=>{let E=await j(f,u,"config");return{path:f,base:E.base,config:E.module,reference:g,src:p}})),Promise.all(i.map(async([{id:f,base:u,reference:g,src:p},E])=>{let x=await j(f,u,"plugin");return{path:f,base:x.base,plugin:x.module,options:E,reference:g,src:p}}))]);return n|=rt({designSystem:A,base:e,ast:t,sources:r,configs:s,pluginDetails:h}),n}function rt({designSystem:A,base:e,ast:t,sources:j,configs:r,pluginDetails:n}){let i=0,l=[...n.map(p=>{if(!p.options)return{config:{plugins:[p.plugin]},base:p.base,reference:p.reference,src:p.src};if("__isOptionsFunction"in p.plugin)return{config:{plugins:[p.plugin(p.options)]},base:p.base,reference:p.reference,src:p.src};throw new Error(`The plugin "${p.path}" does not accept options`)}),...r],{resolvedConfig:c}=tt(A,[{config:Ga(A.theme),base:e,reference:!0,src:void 0},...l,{config:{plugins:[Pa]},base:e,reference:!0,src:void 0}]),{resolvedConfig:s,replacedThemeKeys:h}=tt(A,l),f={designSystem:A,ast:t,resolvedConfig:c,featuresRef:{set current(p){i|=p}}},u=At({...f,referenceMode:!1,src:void 0}),g=A.resolveThemeValue;A.resolveThemeValue=function(p,E){if(p[0]==="-"&&p[1]==="-")return g(p,E);let x=u.theme(p,void 0);if(Array.isArray(x)&&x.length===2)return x[0];if(Array.isArray(x))return x.join(", ");if(typeof x=="string")return x};for(let{handler:p,reference:E,src:x}of c.plugins){let I=At({...f,referenceMode:E??!1,src:x});p(I)}if(la(A,s,h),Ia(A,s,h),Ha(s,A),Ma(s,A),Oa(s,A),!A.theme.prefix&&c.prefix){if(c.prefix.endsWith("-")&&(c.prefix=c.prefix.slice(0,-1),console.warn(`The prefix "${c.prefix}" is invalid. Prefixes must be lowercase ASCII letters (a-z) only and is written as a variant before all utilities. We have fixed up the prefix for you. Remove the trailing \`-\` to silence this warning.`)),!La.test(c.prefix))throw new Error(`The prefix "${c.prefix}" is invalid. Prefixes must be lowercase ASCII letters (a-z) only.`);A.theme.prefix=c.prefix}if(!A.important&&c.important===!0&&(A.important=!0),typeof c.important=="string"){let p=c.important;z(t,(E,{replaceWith:x,parent:I})=>{if(E.kind==="at-rule"&&!(E.name!=="@tailwind"||E.params!=="utilities"))return I?.kind==="rule"&&I.selector===p||x(X(p,[E])),2})}for(let p of c.blocklist)A.invalidCandidates.add(p);for(let p of c.content.files){if("raw"in p)throw new Error(`Error in the config file/plugin/preset. The \`content\` key contains a \`raw\` entry:

${JSON.stringify(p,null,2)}

This feature is not currently supported.`);let E=!1;p.pattern[0]=="!"&&(E=!0,p.pattern=p.pattern.slice(1)),j.push({...p,negated:E})}return i}function Ca(A){let e=[0];for(let r=0;r<A.length;r++)A.charCodeAt(r)===10&&e.push(r+1);function t(r){let n=0,i=e.length;for(;i>0;){let c=(i|0)>>1,s=n+c;e[s]<=r?(n=s+1,i=i-c-1):i=c}n-=1;let l=r-e[n];return{line:n+1,column:l}}function j({line:r,column:n}){r-=1,r=Math.min(Math.max(r,0),e.length-1);let i=e[r],l=e[r+1]??i;return Math.min(Math.max(i+n,0),l)}return{find:t,findOffset:j}}function za({ast:A}){let e=new U(r=>Ca(r.code)),t=new U(r=>({url:r.file,content:r.code,ignore:!1})),j={file:null,sources:[],mappings:[]};z(A,r=>{if(!r.src||!r.dst)return;let n=t.get(r.src[0]);if(!n.content)return;let i=e.get(r.src[0]),l=e.get(r.dst[0]),c=n.content.slice(r.src[1],r.src[2]),s=0;for(let u of c.split(`
`)){if(u.trim()!==""){let g=i.find(r.src[1]+s),p=l.find(r.dst[1]);j.mappings.push({name:null,originalPosition:{source:n,...g},generatedPosition:p})}s+=u.length,s+=1}let h=i.find(r.src[2]),f=l.find(r.dst[2]);j.mappings.push({name:null,originalPosition:{source:n,...h},generatedPosition:f})});for(let r of e.keys())j.sources.push(t.get(r));return j.mappings.sort((r,n)=>r.generatedPosition.line-n.generatedPosition.line||r.generatedPosition.column-n.generatedPosition.column||(r.originalPosition?.line??0)-(n.originalPosition?.line??0)||(r.originalPosition?.column??0)-(n.originalPosition?.column??0)),j}var Bt=/^(-?\d+)\.\.(-?\d+)(?:\.\.(-?\d+))?$/;function Kj(A){let e=A.indexOf("{");if(e===-1)return[A];let t=[],j=A.slice(0,e),r=A.slice(e),n=0,i=r.lastIndexOf("}");for(let f=0;f<r.length;f++){let u=r[f];if(u==="{")n++;else if(u==="}"&&(n--,n===0)){i=f;break}}if(i===-1)throw new Error(`The pattern \`${A}\` is not balanced.`);let l=r.slice(1,i),c=r.slice(i+1),s;Na(l)?s=Ra(l):s=H(l,","),s=s.flatMap(f=>Kj(f));let h=Kj(c);for(let f of h)for(let u of s)t.push(j+u+f);return t}function Na(A){return Bt.test(A)}function Ra(A){let e=A.match(Bt);if(!e)return[A];let[,t,j,r]=e,n=r?parseInt(r,10):void 0,i=[];if(/^-?\d+$/.test(t)&&/^-?\d+$/.test(j)){let l=parseInt(t,10),c=parseInt(j,10);if(n===void 0&&(n=l<=c?1:-1),n===0)throw new Error("Step cannot be zero in sequence expansion.");let s=l<c;s&&n<0&&(n=-n),!s&&n>0&&(n=-n);for(let h=l;s?h<=c:h>=c;h+=n)i.push(h.toString())}return i}var Xa=/^[a-z]+$/,xt=(A=>(A[A.None=0]="None",A[A.AtProperty=1]="AtProperty",A[A.ColorMix=2]="ColorMix",A[A.All=3]="All",A))(xt||{});function Va(){throw new Error("No `loadModule` function provided to `compile`")}function Ja(){throw new Error("No `loadStylesheet` function provided to `compile`")}function Ua(A){let e=0,t=null;for(let j of H(A," "))j==="reference"?e|=2:j==="inline"?e|=1:j==="default"?e|=4:j==="static"?e|=8:j.startsWith("prefix(")&&j.endsWith(")")&&(t=j.slice(7,-1));return[e,t]}var _t=(A=>(A[A.None=0]="None",A[A.AtApply=1]="AtApply",A[A.AtImport=2]="AtImport",A[A.JsPluginCompat=4]="JsPluginCompat",A[A.ThemeFunction=8]="ThemeFunction",A[A.Utilities=16]="Utilities",A[A.Variants=32]="Variants",A))(_t||{});async function Wa(A,{base:e="",from:t,loadModule:j=Va,loadStylesheet:r=Ja}={}){let n=0;A=[kA({base:e},A)],n|=await gt(A,e,r,0,t!==void 0);let i=null,l=new Er,c=[],s=[],h=null,f=null,u=[],g=[],p=[],E=[],x=null;z(A,(b,{parent:G,replaceWith:w,context:P})=>{if(b.kind==="at-rule"){if(b.name==="@tailwind"&&(b.params==="utilities"||b.params.startsWith("utilities"))){if(f!==null){w([]);return}if(P.reference){w([]);return}let K=H(b.params," ");for(let D of K)if(D.startsWith("source(")){let O=D.slice(7,-1);if(O==="none"){x=O;continue}if(O[0]==='"'&&O[O.length-1]!=='"'||O[0]==="'"&&O[O.length-1]!=="'"||O[0]!=="'"&&O[0]!=='"')throw new Error("`source(\u2026)` paths must be quoted.");x={base:P.sourceBase??P.base,pattern:O.slice(1,-1)}}f=b,n|=16}if(b.name==="@utility"){if(G!==null)throw new Error("`@utility` cannot be nested.");if(b.nodes.length===0)throw new Error(`\`@utility ${b.params}\` is empty. Utilities should include at least one property.`);let K=Rr(b);if(K===null){if(!b.params.endsWith("-*")){if(b.params.endsWith("*"))throw new Error(`\`@utility ${b.params}\` defines an invalid utility name. A functional utility must end in \`-*\`.`);if(b.params.includes("*"))throw new Error(`\`@utility ${b.params}\` defines an invalid utility name. The dynamic portion marked by \`-*\` must appear once at the end.`)}throw new Error(`\`@utility ${b.params}\` defines an invalid utility name. Utilities should be alphanumeric and start with a lowercase letter.`)}s.push(K)}if(b.name==="@source"){if(b.nodes.length>0)throw new Error("`@source` cannot have a body.");if(G!==null)throw new Error("`@source` cannot be nested.");let K=!1,D=!1,O=b.params;if(O[0]==="n"&&O.startsWith("not ")&&(K=!0,O=O.slice(4)),O[0]==="i"&&O.startsWith("inline(")&&(D=!0,O=O.slice(7,-1)),O[0]==='"'&&O[O.length-1]!=='"'||O[0]==="'"&&O[O.length-1]!=="'"||O[0]!=="'"&&O[0]!=='"')throw new Error("`@source` paths must be quoted.");let N=O.slice(1,-1);if(D){let M=K?E:p,L=H(N," ");for(let J of L)for(let tA of Kj(J))M.push(tA)}else g.push({base:P.base,pattern:N,negated:K});w([]);return}if(b.name==="@variant"&&(G===null?b.nodes.length===0?b.name="@custom-variant":(z(b.nodes,K=>{if(K.kind==="at-rule"&&K.name==="@slot")return b.name="@custom-variant",2}),b.name==="@variant"&&u.push(b)):u.push(b)),b.name==="@custom-variant"){if(G!==null)throw new Error("`@custom-variant` cannot be nested.");w([]);let[K,D]=H(b.params," ");if(!ut.test(K))throw new Error(`\`@custom-variant ${K}\` defines an invalid variant name. Variants should only contain alphanumeric, dashes or underscore characters.`);if(b.nodes.length>0&&D)throw new Error(`\`@custom-variant ${K}\` cannot have both a selector and a body.`);if(b.nodes.length===0){if(!D)throw new Error(`\`@custom-variant ${K}\` has no selector or body.`);let O=H(D.slice(1,-1),",");if(O.length===0||O.some(L=>L.trim()===""))throw new Error(`\`@custom-variant ${K} (${O.join(",")})\` selector is invalid.`);let N=[],M=[];for(let L of O)L=L.trim(),L[0]==="@"?N.push(L):M.push(L);c.push(L=>{L.variants.static(K,J=>{let tA=[];M.length>0&&tA.push(X(M.join(", "),J.nodes));for(let a of N)tA.push(Z(a,J.nodes));J.nodes=tA},{compounds:KA([...M,...N])})});return}else{c.push(O=>{O.variants.fromAst(K,b.nodes)});return}}if(b.name==="@media"){let K=H(b.params," "),D=[];for(let O of K)if(O.startsWith("source(")){let N=O.slice(7,-1);z(b.nodes,(M,{replaceWith:L})=>{if(M.kind==="at-rule"&&M.name==="@tailwind"&&M.params==="utilities")return M.params+=` source(${N})`,L([kA({sourceBase:P.base},[M])]),2})}else if(O.startsWith("theme(")){let N=O.slice(6,-1),M=N.includes("reference");z(b.nodes,L=>{if(L.kind!=="at-rule"){if(M)throw new Error('Files imported with `@import "\u2026" theme(reference)` must only contain `@theme` blocks.\nUse `@reference "\u2026";` instead.');return 0}if(L.name==="@theme")return L.params+=" "+N,1})}else if(O.startsWith("prefix(")){let N=O.slice(7,-1);z(b.nodes,M=>{if(M.kind==="at-rule"&&M.name==="@theme")return M.params+=` prefix(${N})`,1})}else O==="important"?i=!0:O==="reference"?b.nodes=[kA({reference:!0},b.nodes)]:D.push(O);D.length>0?b.params=D.join(" "):K.length>0&&w(b.nodes)}if(b.name==="@theme"){let[K,D]=Ua(b.params);if(P.reference&&(K|=2),D){if(!Xa.test(D))throw new Error(`The prefix "${D}" is invalid. Prefixes must be lowercase ASCII letters (a-z) only.`);l.prefix=D}return z(b.nodes,O=>{if(O.kind==="at-rule"&&O.name==="@keyframes")return l.addKeyframes(O),1;if(O.kind==="comment")return;if(O.kind==="declaration"&&O.property.startsWith("--")){l.add(Aj(O.property),O.value??"",K,O.src);return}let N=EA([R(b.name,b.params,[O])]).split(`
`).map((M,L,J)=>`${L===0||L>=J.length-2?" ":">"} ${M}`).join(`
`);throw new Error(`\`@theme\` blocks must only contain custom properties or \`@keyframes\`.

${N}`)}),h?w([]):(h=X(":root, :host",[]),h.src=b.src,w([h])),1}}});let I=aa(l);if(i&&(I.important=i),E.length>0)for(let b of E)I.invalidCandidates.add(b);n|=await Sa({designSystem:I,base:e,ast:A,loadModule:j,sources:g});for(let b of c)b(I);for(let b of s)b(I);if(h){let b=[];for(let[w,P]of I.theme.entries()){if(P.options&2)continue;let K=o(oj(w),P.value);K.src=P.src,b.push(K)}let G=I.theme.getKeyframes();for(let w of G)A.push(kA({theme:!0},[S([w])]));h.nodes=[kA({theme:!0},b)]}if(u.length>0){for(let b of u){let G=X("&",b.nodes),w=b.params,P=I.parseVariant(w);if(P===null)throw new Error(`Cannot use \`@variant\` with unknown variant: ${w}`);if(cj(G,P,I.variants)===null)throw new Error(`Cannot use \`@variant\` with variant: ${w}`);Object.assign(b,G)}n|=32}if(n|=Tj(A,I),n|=Fj(A,I),f){let b=f;b.kind="context",b.context={}}return z(A,(b,{replaceWith:G})=>{if(b.kind==="at-rule")return b.name==="@utility"&&G([]),1}),{designSystem:I,ast:A,sources:g,root:x,utilitiesNode:f,features:n,inlineCandidates:p}}async function vt(A,e={}){let{designSystem:t,ast:j,sources:r,root:n,utilitiesNode:i,features:l,inlineCandidates:c}=await Wa(A,e);j.unshift(ct(`! tailwindcss v${mr} | MIT License | https://tailwindcss.com `));function s(p){t.invalidCandidates.add(p)}let h=new Set,f=null,u=0,g=!1;for(let p of c)t.invalidCandidates.has(p)||(h.add(p),g=!0);return{sources:r,root:n,features:l,build(p){if(l===0)return A;if(!i)return f??(f=yA(j,t,e.polyfills)),f;let E=g,x=!1;g=!1;let I=h.size;for(let G of p)if(!t.invalidCandidates.has(G))if(G[0]==="-"&&G[1]==="-"){let w=t.theme.markUsedVariable(G);E||(E=w),x||(x=w)}else h.add(G),E||(E=h.size!==I);if(!E)return f??(f=yA(j,t,e.polyfills)),f;let b=ij(h,t,{onInvalidCandidate:s}).astNodes;return e.from&&z(b,G=>{G.src??(G.src=i.src)}),!x&&u===b.length?(f??(f=yA(j,t,e.polyfills)),f):(u=b.length,i.nodes=b,f=yA(j,t,e.polyfills),f)}}}async function Lj(A,e={}){let t=Pj(A,{from:e.from}),j=await vt(t,e),r=t,n=A;return{...j,build(i){let l=j.build(i);return l===r||(n=EA(l,!!e.from),r=l),n},buildSourceMap(){return za({ast:r})}}}async function Ya({content:A="",css:e="",importCSS:t='@import "tailwindcss";',candidates:j=[]}){j.length||(j=await fj({content:A}));let r=e?`${t}
${e}`:t;return(await Lj(r,{base:"/",loadStylesheet:nj,loadModule:()=>{throw new Error("External modules not supported in browser build")}})).build(j)}export{Ya as generateTailwindCSS,fj as getTailwindClasses,nj as loadTailwindCSS};
//# sourceMappingURL=browser.js.map