const {test}=require('node:test');
const assert=require('node:assert/strict');
const fs=require('node:fs');
const vm=require('node:vm');
const ts=require('typescript');

test('riwayat mengambil ulang data saat fokus dan membatalkan request saat blur',async()=>{
 let focus;
 const requests=[];
 const module={exports:{}};
 const source=ts.transpileModule(fs.readFileSync('src/features/reporting/history.tsx','utf8'),{
  compilerOptions:{module:ts.ModuleKind.CommonJS,jsx:ts.JsxEmit.ReactJSX},
 }).outputText;
 const dependencies={
  react:{useState:value=>[value,()=>{}],useCallback:callback=>callback},
  'react-native':{ActivityIndicator:'indicator',Image:'image',Text:'text',View:'view',StyleSheet:{create:value=>value}},
  'react-native-safe-area-context':{SafeAreaView:'safe'},
  'expo-symbols':{SymbolView:'symbol'},
  '../dashboard/screen':{AppBottomNav:'nav'},
  '../../components/report-image':{default:'report-image'},
  './history-data':{visibleHistory:reports=>reports},
  'expo-router':{Link:'link',useFocusEffect:callback=>{focus=callback;}},
  '../../services/api/client':{apiClient:{request:async(path,options)=>{
   requests.push({path,options});return {data:[],pagination:{current_page:1,last_page:1}};
  }}},
  '../auth/screen':{Action:'action',AuthShell:'shell',styles:{}},
  '../auth/provider':{useAuth:()=>({restore:async()=>{}})},
  '../dashboard/service':{statusLabels:{pending:'Menunggu'}},
 };
 vm.runInNewContext('(function(require,module,exports){'+source+'})',{AbortController})(
  name=>name in dependencies?dependencies[name]:require(name),module,module.exports,
 );
 module.exports.default();
 assert.equal(typeof focus,'function');
 const blur=focus();await new Promise(resolve=>setImmediate(resolve));
 assert.equal(requests[0].path,'reports');
 assert.equal(requests[0].options.query.page,1);
 assert.equal(requests[0].options.signal.aborted,false);
 blur();assert.equal(requests[0].options.signal.aborted,true);
 const secondBlur=focus();await new Promise(resolve=>setImmediate(resolve));
 assert.equal(requests.length,2);
 assert.notEqual(requests[0].options.signal,requests[1].options.signal);
 secondBlur();
});
