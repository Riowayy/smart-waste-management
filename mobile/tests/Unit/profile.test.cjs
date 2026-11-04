const {test}=require('node:test');
const assert=require('node:assert/strict');
const fs=require('node:fs');
const vm=require('node:vm');
const ts=require('typescript');
function profile(logout=async()=>{}, getDashboard=async()=>({statistics:{total:0}}), options={}) {
 const states=[],refs=[];let index=0,refIndex=0,focus;
 const dialogs=[];let navigationLocked=false;
 const module={exports:{}};
 const source=ts.transpileModule(fs.readFileSync('src/features/auth/profile.tsx','utf8'),{compilerOptions:{module:ts.ModuleKind.CommonJS,jsx:ts.JsxEmit.ReactJSX}}).outputText;
 class ApiError extends Error {}
 const dependencies={'../../components/eco-modal':{__esModule:true,default:'eco-modal'},
  react:{useState:initial=>{const i=index++;if(!(i in states))states[i]=initial;return [states[i],v=>{states[i]=typeof v==='function'?v(states[i]):v;}];},useRef:initial=>refs[refIndex++]??(refs[refIndex-1]={current:initial}),useCallback:fn=>fn},
  'react-native':{Platform:{OS:options.platform??'android'},Alert:{alert:(title,message,buttons,settings)=>dialogs.push({title,message,buttons,settings})},StyleSheet:{create:s=>s},View:'view',Text:'text',ScrollView:'scroll',Pressable:'button',ActivityIndicator:'loading'},
  'react-native-safe-area-context':{SafeAreaView:'safe'},'expo-router':{Link:'link',useFocusEffect:fn=>{focus=fn;}},'expo-symbols':{SymbolView:'symbol'},
  '../../components/navigation-lock':{useNavigationLock:()=>({acquire:()=>{navigationLocked=true;return ()=>{navigationLocked=false;};}})},'../dashboard/service':{getDashboard},'../../services/api/client':{ApiError},
  './provider':{useAuth:()=>({user:{name:'Ayu Lestari',email:'ayu@example.test',role:'user'},logout,restore:async()=>{}})},
 };
 vm.runInNewContext('(function(require,module,exports){'+source+'})',{AbortController})(name=>dependencies[name]??require(name),module,module.exports);
 function flatten(node,all=[]) {if(Array.isArray(node))node.forEach(n=>flatten(n,all));else if(node&&typeof node==='object'&&node.props){all.push(node);flatten(node.props.children,all);}else if(node!==null&&node!==undefined)all.push(node);return all;}
 return {states,dialogs,navigationLocked:()=>navigationLocked,render(){index=0;refIndex=0;return flatten(module.exports.default());},focus:()=>focus()};
}
const tick=()=>new Promise(resolve=>setImmediate(resolve));
function logoutButton(nodes) {return nodes.find(n=>n?.props?.accessibilityState?.busy!==undefined&&n.type==='button');}
test('profil memakai identitas sesi, inisial, dan navigasi Profil aktif tanpa data contoh',()=>{
 const view=profile(),nodes=view.render();
 assert.ok(nodes.includes('Ayu Lestari'));assert.ok(nodes.includes('ayu@example.test'));assert.ok(nodes.includes('AL'));
 assert.equal(nodes.some(n=>n?.type==='nav'),false);
 assert.ok(nodes.some(n=>n?.type==='link'&&n.props.href==='/reports'));
 assert.ok(!nodes.includes('Bandung'));assert.ok(!nodes.includes('Edit Profil'));
});
test('jumlah laporan nol berasal dari API, request dibatalkan ketika profil kehilangan fokus',async()=>{
 let signal;const view=profile(undefined,async s=>{signal=s;return {statistics:{total:0}};});
 view.render();const blur=view.focus();await tick();
 assert.equal(view.states[2],0);assert.equal(view.states[3],false);
 blur();assert.equal(signal.aborted,true);
});
test('kegagalan statistik tidak mengarang total dan dapat dicoba lagi',async()=>{
 const view=profile(undefined,async()=>{throw new Error('offline');});view.render();view.focus();await tick();
 assert.equal(view.states[2],null);assert.equal(view.states[4],true);
 const retry=view.render().find(n=>n?.type==='button'&&n.props.children?.props?.children==='Coba lagi');
 assert.ok(retry);retry.props.onPress();assert.equal(view.states[5],1);
});
const modal=view=>view.render().find(node=>node?.type==='eco-modal')?.props;
const choice=(view,label)=>view.render().find(node=>node?.props?.accessibilityLabel===label)?.props;
test('logout menahan klik ganda dan menampilkan kegagalan sebelum percobaan ulang',async()=>{
 let calls=0,reject;
 const view=profile(()=>{calls++;return new Promise((_,r)=>{reject=r;});});
 const button=logoutButton(view.render());button.props.onPress();button.props.onPress();
 assert.equal(calls,0);assert.ok(modal(view));assert.equal(view.navigationLocked(),false);
 const yes=choice(view,'Keluar');yes.onPress();yes.onPress();
 assert.equal(calls,1);assert.equal(logoutButton(view.render()).props.disabled,true);assert.equal(view.navigationLocked(),true);
 reject(new Error('offline'));await tick();
 assert.equal(view.navigationLocked(),false);assert.match(view.states[1],/Belum dapat keluar/);assert.equal(logoutButton(view.render()).props.disabled,false);
 logoutButton(view.render()).props.onPress();choice(view,'Keluar').onPress();assert.equal(calls,2);reject(new Error('offline'));await tick();
});
test('Batal atau Back modal logout mempertahankan sesi dan request statistik',async()=>{
 let calls=0,signal;
 const view=profile(async()=>{calls++;},async s=>{signal=s;return {statistics:{total:0}};});view.render();view.focus();await tick();
 logoutButton(view.render()).props.onPress();assert.ok(choice(view,'Batal'));assert.ok(choice(view,'Keluar'));
 choice(view,'Batal').onPress();assert.equal(modal(view),undefined);assert.equal(calls,0);assert.equal(signal.aborted,false);
 logoutButton(view.render()).props.onPress();modal(view).onClose();assert.equal(modal(view),undefined);assert.equal(calls,0);assert.equal(signal.aborted,false);assert.equal(view.navigationLocked(),false);
 logoutButton(view.render()).props.onPress();assert.ok(modal(view));
});
test('konfirmasi logout Web menjalankan logout hanya setelah Keluar dipilih',async()=>{
 let calls=0;const view=profile(async()=>{calls++;},undefined,{platform:'web'});
 logoutButton(view.render()).props.onPress();assert.equal(calls,0);assert.ok(modal(view));
 choice(view,'Batal').onPress();assert.equal(calls,0);assert.equal(choice(view,'Keluar'),undefined);
 logoutButton(view.render()).props.onPress();choice(view,'Keluar').onPress();await tick();assert.equal(calls,1);assert.equal(modal(view),undefined);
});