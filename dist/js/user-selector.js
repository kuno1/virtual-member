!function(){"use strict";var e=window.ReactJSXRuntime;const{useState:n,useEffect:s}=wp.element,{Button:t,Icon:o,ComboboxControl:r}=wp.components,{apiFetch:a}=wp,c=({user:n,onUserDelete:s})=>(0,e.jsxs)(t,{className:"kvm-user-token",onClick:()=>s(n),iconPosition:"right",variant:"secondary",children:[n.name,(0,e.jsxs)("small",{children:["(",n.group.length>0?n.group.map((e=>e.name)).join(", "):"---",")"]}),(0,e.jsx)(o,{icon:"no-alt"})]}),l=({onUserSelected:t})=>{const[o,c]=n(""),[l,i]=n([]),[m,u]=n(null),[h,d]=n(null);return s((()=>{h&&clearTimeout(h),d(setTimeout((()=>{if(o){const e=async()=>{const e=await a({path:`/kvm/v1/authors/search?s=${o}`});i(e)};try{e()}catch(e){i([])}}else i([])}),300))}),[o]),(0,e.jsx)(r,{label:KvmUserSelector.slabel,placeholder:KvmUserSelector.search,value:m,options:l.map((e=>({label:e.name,value:e.id}))),onChange:e=>{l.forEach((n=>{n.id===e&&t(n)})),u("")},onFilterValueChange:e=>c(e)})},i=window.kvm||{};i.UserSelectorComponent=({post:t,onUserChange:o})=>{const[r,i]=n([]);return s((()=>{(async()=>{try{const e=await a({path:`/kvm/v1/authors/of/${t}`});i(e)}catch(e){alert(e.message||"Error")}})()}),[]),s((()=>{o(r)}),[r]),(0,e.jsxs)(e.Fragment,{children:[r.length>0?(0,e.jsx)("div",{className:"kvm-user-token-wrapper",children:r.map((n=>(0,e.jsx)(c,{user:n,onUserDelete:e=>(e=>{const n=[];r.map((s=>(s.id!==e.id&&n.push(s),s))),i(n)})(e)},n.id)))}):(0,e.jsx)("div",{className:"notice notice-error notice-alt",children:(0,e.jsx)("p",{children:KvmUserSelector.nouser})}),(0,e.jsx)(l,{onUserSelected:e=>{r.map((e=>e.id)).includes(e.id)||i(r.concat([e]))}})]})},window.kvm=i}();