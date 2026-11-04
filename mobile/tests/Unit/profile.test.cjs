const {test}=require('node:test');
const assert=require('node:assert/strict');
const fs=require('node:fs');
const vm=require('node:vm');
const ts=require('typescript');
function profile(logout=async()=>{}, getDashboard=async()=>({statistics:{total:0}}), options={}) {
 const states=[],refs=[];let index=0,refIndex=0,focus;
 const dialogs=[];
 const module={exports:{}};
 const source=ts.transpileModule(fs.readFileSync('src/features/auth/profile.tsx','utf8'),{compilerOptions:{module:ts.ModuleKind.CommonJS,jsx:ts.JsxEmit.ReactJSX}}).outputText;
 class ApiError extends Error {}
 const dependencies={
  react:{useState:initial=>{const i=index++;if(!(i in states))states[i]=initial;return [states[i],v=>{states[i]=typeof v==='function'?v(states[i]):v;}];},useRef:initial=>refs[refIndex++]??(refs[refIndex-1]={current:initial}),useCallback:fn=>fn},
  'react-native':{Platform:{OS:options.platform??'android'},Alert:{alert:(title,message,buttons,settings)=>dialogs.push({title,message,buttons,settings})},StyleSheet:{create:s=>s},View:'view',Text:'text',ScrollView:'scroll',Pressable:'button',ActivityIndicator:'loading'},
  'react-native-safe-area-context':{SafeAreaView:'safe'},'expo-router':{Link:'link',useFocusEffect:fn=>{focus=fn;}},'expo-symbols':{SymbolView:'symbol'},
  '../dashboard/screen':{AppBottomNav:'nav'},'../dashboard/service':{getDashboard},'../../services/api/client':{ApiError},
  './provider':{useAuth:()=>({user:{name:'Ayu Lestari',email:'ayu@example.test',role:'user'},logout,restore:async()=>{}})},
 };
 vm.runInNewContext('(function(require,module,exports){'+source+'})',{AbortController})(name=>dependencies[name]??require(name),module,module.exports);
 function flatten(node,all=[]) {if(Array.isArray(node))node.forEach(n=>flatten(n,all));else if(node&&typeof node==='object'&&node.props){all.push(node);flatten(node.props.children,all);}else if(node!==null&&node!==undefined)all.push(node);return all;}
 return {states,dialogs,render(){index=0;refIndex=0;return flatten(module.exports.default());},focus:()=>focus()};
}
const tick=()=>new Promise(resolve=>setImmediate(resolve));
function logoutButton(nodes) {return nodes.find(n=>n?.props?.accessibilityState?.busy!==undefined&&n.type==='button');}
test('profil memakai identitas sesi, inisial, dan navigasi Profil aktif tanpa data contoh',()=>{
 const view=profile(),nodes=view.render();
 assert.ok(nodes.includes('Ayu Lestari'));assert.ok(nodes.includes('ayu@example.test'));assert.ok(nodes.includes('AL'));
 assert.ok(nodes.some(n=>n?.type==='nav'&&n.props.active==='/profile'));
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
test('logout menahan klik ganda dan menampilkan kegagalan sebelum percobaan ulang',async()=>{
 let calls=0,reject;
 const view=profile(()=>{calls++;return new Promise((_,r)=>{reject=r;});});
 const button=logoutButton(view.render());button.props.onPress();button.props.onPress();
 assert.equal(calls,0);assert.equal(view.dialogs.length,1);
 const yes=view.dialogs[0].buttons.find(choice=>choice.text==='Ya');yes.onPress();yes.onPress();
 assert.equal(calls,1);assert.equal(logoutButton(view.render()).props.disabled,true);
 reject(new Error('offline'));await tick();
 assert.match(view.states[1],/Belum dapat keluar/);assert.equal(logoutButton(view.render()).props.disabled,false);
 logoutButton(view.render()).props.onPress();assert.equal(calls,1);
 view.dialogs.at(-1).buttons.find(choice=>choice.text==='Ya').onPress();assert.equal(calls,2);reject(new Error('offline'));await tick();
});

test('Tidak atau menutup dialog logout mempertahankan sesi dan request statistik',async()=>{
 let calls=0,signal;
 const view=profile(async()=>{calls++;},async s=>{signal=s;return {statistics:{total:0}};});
 view.render();view.focus();await tick();
 logoutButton(view.render()).props.onPress();
 assert.deepEqual(Array.from(view.dialogs[0].buttons,choice=>choice.text),['Tidak','Ya']);
 view.dialogs[0].buttons.find(choice=>choice.text==='Tidak').onPress();
 assert.equal(calls,0);assert.equal(signal.aborted,false);assert.equal(view.states[0],false);
 logoutButton(view.render()).props.onPress();view.dialogs.at(-1).settings.onDismiss();
 assert.equal(calls,0);assert.equal(signal.aborted,false);
 logoutButton(view.render()).props.onPress();assert.equal(view.dialogs.length,3);
});

test('konfirmasi logout Web menjalankan logout hanya setelah Ya dipilih',async()=>{
 let calls=0;
 const view=profile(async()=>{calls++;},undefined,{platform:'web'});
 const choice=label=>view.render().find(node=>node?.props?.accessibilityLabel===label)?.props;
 logoutButton(view.render()).props.onPress();
 assert.equal(calls,0);assert.equal(view.dialogs.length,0);assert.ok(choice('Ya'));assert.ok(choice('Tidak'));
 choice('Tidak').onPress();assert.equal(calls,0);assert.equal(choice('Ya'),undefined);
 logoutButton(view.render()).props.onPress();choice('Ya').onPress();await tick();
 assert.equal(calls,1);assert.equal(choice('Ya'),undefined);
});
