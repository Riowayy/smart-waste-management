const {test}=require('node:test');const assert=require('node:assert/strict');const fs=require('node:fs');const vm=require('node:vm');const ts=require('typescript');
function setup(failure){let token='old';let calls=[];class ApiError extends Error{constructor(status){super('Gagal');this.status=status;}}
const storage={getToken:async()=>token,setToken:async v=>{token=v;},clearToken:async()=>{token=null;}};
const apiClient={request:async(path,options)=>{calls.push({path,options});if(failure)throw failure==='network'?new Error('offline'):new ApiError(failure);return {data:path==='auth/me'?{id:1,name:'Ayu',role:'user'}:{user:{id:1,name:'Ayu',role:'user'},token:'new'}};}};
const module={exports:{}};const source=ts.transpileModule(fs.readFileSync('src/features/auth/session.ts','utf8'),{compilerOptions:{module:ts.ModuleKind.CommonJS,target:ts.ScriptTarget.ES2022}}).outputText;
vm.runInNewContext('(function(require,module,exports){'+source+'})')((name)=>name.includes('token-storage')?{tokenStorage:storage}:{apiClient,ApiError},module,module.exports);
return {...module.exports,calls,token:()=>token};}
test('login dan register menyimpan token sebelum mengembalikan user',async()=>{for(const mode of ['login','register']){const s=setup();assert.equal((await s.session.authenticate(mode,{email:'a@b.id',password:'password'})).id,1);assert.equal(s.token(),'new');assert.equal(s.calls[0].path,'auth/'+mode);assert.equal(s.calls[0].options.authenticated,false);}});
test('pemulihan sesi memakai auth/me',async()=>{const s=setup();assert.equal((await s.session.restore()).id,1);assert.equal(s.calls[0].path,'auth/me');});
test('token invalid dibersihkan',async()=>{for(const status of [401,403]){const s=setup(status);assert.equal(await s.session.restore(),null);assert.equal(s.token(),null);}});
test('network error pemulihan tidak menghapus token',async()=>{const s=setup('network');await assert.rejects(s.session.restore());assert.equal(s.token(),'old');});
test('logout normal dan token kedaluwarsa membersihkan token',async()=>{for(const status of [undefined,401]){const s=setup(status);await s.session.logout();assert.equal(s.token(),null);}});
test('logout gagal dapat dicoba kembali',async()=>{const s=setup('network');await assert.rejects(s.session.logout());assert.equal(s.token(),'old');});
test('validasi Indonesia dan error API diteruskan',async()=>{const s=setup(422);assert.ok(s.validateCredentials('register',{email:'bad',password:'x',name:'',password_confirmation:'z'}).password);await assert.rejects(s.session.authenticate('register',{email:'a@b.id',password:'password'}),e=>e.status===422);});
test('protected routes hanya merender route sesuai sesi dan menahan navigasi saat loading',()=>{
 const React=require('react');const {renderToStaticMarkup}=require('react-dom/server');
 for(const user of [null,{id:1}])for(const status of ['ready','loading']){
  const Stack=({children})=>children;Stack.Protected=({guard,children})=>guard?children:null;Stack.Screen=({name})=>React.createElement('span',null,name);
  const module={exports:{}};const source=ts.transpileModule(fs.readFileSync('src/app/_layout.tsx','utf8'),{compilerOptions:{module:ts.ModuleKind.CommonJS,target:ts.ScriptTarget.ES2022,jsx:ts.JsxEmit.ReactJSX}}).outputText;
  const deps={'expo-router':{Stack},'react-native':{ActivityIndicator:()=>null,Text:({children})=>children},'../features/auth/provider':{AuthProvider:({children})=>children,useAuth:()=>({user,status})},'../features/auth/screen':{Action:()=>null,AuthShell:({children})=>children,styles:{}}};
  vm.runInNewContext('(function(require,module,exports){'+source+'})')(name=>deps[name]??require(name),module,module.exports);
  const html=renderToStaticMarkup(React.createElement(module.exports.default));
  if(status==='loading')assert.equal(html,'');else if(user){assert.ok(html.includes('index'));assert.ok(html.includes('reports'));assert.ok(html.includes('create-report'));assert.ok(!html.includes('login'));}else{assert.ok(html.includes('login'));assert.ok(html.includes('register'));assert.ok(!html.includes('index'));assert.ok(!html.includes('reports'));assert.ok(!html.includes('create-report'));}
 }
});
