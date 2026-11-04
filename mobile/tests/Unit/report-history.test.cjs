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
  react:{useState:value=>[value,()=>{}],useCallback:callback=>callback,useRef:()=>({current:null})},
  'react-native':{ActivityIndicator:'indicator',Image:'image',Text:'text',View:'view',StyleSheet:{create:value=>value}},
  'react-native-safe-area-context':{SafeAreaView:'safe'},
  'expo-symbols':{SymbolView:'symbol'},
  '../dashboard/screen':{AppBottomNav:'nav'},
  '../../components/report-image':{default:'report-image'},
  './history-data':{visibleHistory:reports=>reports,HISTORY_PAGE_SIZE:10},
  './location-map':{default:'map'},
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
 assert.equal(requests[0].options.query.per_page,10);
 assert.equal(requests[0].options.signal.aborted,false);
 blur();assert.equal(requests[0].options.signal.aborted,true);
 const secondBlur=focus();await new Promise(resolve=>setImmediate(resolve));
 assert.equal(requests.length,2);
 assert.notEqual(requests[0].options.signal,requests[1].options.signal);
 secondBlur();
});

function historyView({page=1,latitude=0,longitude=180,openURL=async()=>{}}={}) {
 const states=[], scrolls=[], requests=[];let index=0,focus;
 const report={id:6,description:'Temuan kertas',category:null,status:'pending',created_at:null,image_url:null,latitude,longitude,status_history:[]};
 const pagination={current_page:page,last_page:2,per_page:10,total:11};
 const dataModule={exports:{}};
 const dataSource=ts.transpileModule(fs.readFileSync('src/features/reporting/history-data.ts','utf8'),{compilerOptions:{module:ts.ModuleKind.CommonJS}}).outputText;
 vm.runInNewContext('(function(module,exports){'+dataSource+'})')(dataModule,dataModule.exports);
 const initial={2:page,5:[report],7:pagination,8:false};
 const module={exports:{}};
 const source=ts.transpileModule(fs.readFileSync('src/features/reporting/history.tsx','utf8'),{compilerOptions:{module:ts.ModuleKind.CommonJS,jsx:ts.JsxEmit.ReactJSX}}).outputText;
 const dependencies={
  react:{useCallback:callback=>callback,useRef:()=>({current:{scrollTo:options=>scrolls.push(options)}}),useState:value=>{const i=index++;if(!(i in states))states[i]=i in initial?initial[i]:value;return [states[i],next=>{states[i]=typeof next==='function'?next(states[i]):next;}];}},
  'react-native':{View:'view',Text:'text',Pressable:'button',ScrollView:'scroll',TextInput:'input',RefreshControl:'refresh',ActivityIndicator:'loading',Linking:{openURL},StyleSheet:{create:value=>value}},
  'react-native-safe-area-context':{SafeAreaView:'safe'},'expo-symbols':{SymbolView:'symbol'},
  '../dashboard/screen':{AppBottomNav:'nav'},'../../components/report-image':{default:'image'},'./location-map':{default:'map'},
  './history-data':dataModule.exports,
  'expo-router':{Link:'link',useFocusEffect:callback=>{focus=callback;}},
  '../auth/provider':{useAuth:()=>({restore:async()=>{}})},
  '../dashboard/service':{formatReportDate:()=>'',statusLabels:{pending:'Menunggu',in_progress:'Sedang diproses',completed:'Selesai',rejected:'Ditolak'}},
  '../../services/api/client':{ApiError:class extends Error{},apiClient:{request:async(path,options)=>{requests.push({path,options});return {data:path==='reports'?[report]:report,pagination};}}},
 };
 vm.runInNewContext('(function(require,module,exports){'+source+'})',{AbortController})(name=>name in dependencies?dependencies[name]:require(name),module,module.exports);
 function nodes(node,result=[]) {
  if(Array.isArray(node)){for(const item of node)nodes(item,result);}
  else if(node&&typeof node==='object'&&node.props){result.push(node);nodes(node.props.children,result);}
  return result;
 }
 return {states,scrolls,requests,render(){index=0;return nodes(module.exports.default());},async load(){const blur=focus();await new Promise(resolve=>setImmediate(resolve));return blur;},dropdown(node){index=12;return nodes(node.type(node.props));}};
}
const buttonText=(nodes,label)=>nodes.find(node=>node.type==='button'&&node.props.children?.props?.children===label)?.props;

test('halaman pertama menyembunyikan Sebelumnya; perpindahan halaman meminta maksimal10 dan reset filter',async()=>{
 const view=historyView();let nodes=view.render();
 assert.equal(buttonText(nodes,'Sebelumnya'),undefined);
 buttonText(nodes,'Berikutnya').onPress();nodes=view.render();await view.load();
 assert.equal(view.requests.at(-1).options.query.page,2);
 assert.equal(view.requests.at(-1).options.query.per_page,10);
 nodes=view.render();assert.ok(buttonText(nodes,'Sebelumnya'));assert.equal(buttonText(nodes,'Berikutnya').disabled,true);
 const status=nodes.find(node=>node.props.label==='Status laporan');
 status.props.onChange('completed');view.render();await view.load();
 assert.equal(view.requests.at(-1).options.query.page,1);
 assert.equal(view.requests.at(-1).options.query.status,'completed');
 assert.ok(view.scrolls.length);
});

test('ikon filter hanya membuka pilihan status saat ditekan dan menutup setelah dipilih',()=>{
 const view=historyView();const nodes=view.render();
 const status=nodes.find(node=>node.props.label==='Status laporan');
 assert.equal(nodes.some(node=>node.props.label==='Urutan halaman'),false);
 let dropdown=view.dropdown(status);
 assert.equal(dropdown.some(node=>node.props.accessibilityRole==='radio'),false);
 const toggle=dropdown.find(node=>node.props.accessibilityRole==='button');
 assert.equal(toggle.props.accessibilityState.expanded,false);
 assert.equal(toggle.props.children.props.name,'filter_list');
 toggle.props.onPress();
 dropdown=view.dropdown(status);
 assert.deepEqual(dropdown.filter(node=>node.props.accessibilityRole==='radio').map(node=>node.props.accessibilityLabel),['Semua status','Menunggu','Sedang diproses','Selesai','Ditolak']);
 dropdown.find(node=>node.props.accessibilityLabel==='Ditolak').props.onPress();
 assert.equal(view.states[3],'rejected');assert.equal(view.dropdown(status).some(node=>node.props.accessibilityRole==='radio'),false);
});

test('detail meminta endpoint existing dan menampilkan breadcrumb, peta koordinat nol, serta tautan Google Maps',async()=>{
 const urls=[];const view=historyView({openURL:async url=>urls.push(url)});
 view.render().find(node=>node.props.accessibilityLabel?.startsWith('Buka detail laporan')).props.onPress();
 view.render();await view.load();let nodes=view.render();
 assert.equal(view.requests.at(-1).path,'reports/6');
 const map=nodes.find(node=>node.props.point);assert.equal(map.props.point.latitude,0);assert.equal(map.props.point.longitude,180);
 await nodes.find(node=>node.props.accessibilityLabel==='Lihat lokasi laporan di Google Maps').props.onPress();
 const url=new URL(urls[0]);assert.equal(url.origin,'https://www.google.com');assert.equal(url.searchParams.get('api'),'1');assert.equal(url.searchParams.get('query'),'0,180');
 assert.equal(nodes.filter(node=>node.props.accessibilityLabel==='Kembali ke riwayat').length,1);
 assert.ok(nodes.findIndex(node=>node.props.accessibilityLabel==='Kembali ke riwayat')<nodes.findIndex(node=>node.props.children==='Detail Laporan'));
 nodes.find(node=>node.props.accessibilityLabel==='Kembali ke riwayat').props.onPress();view.render();await view.load();assert.equal(view.requests.at(-1).path,'reports');
});

test('detail tanpa pasangan koordinat valid tidak menampilkan peta atau Google Maps',async()=>{
 for(const coordinates of [{latitude:null,longitude:null},{latitude:0,longitude:null},{latitude:91,longitude:180}]){
  const view=historyView(coordinates);view.render().find(node=>node.props.accessibilityLabel?.startsWith('Buka detail laporan')).props.onPress();view.render();await view.load();
  const nodes=view.render();assert.equal(nodes.some(node=>node.props.point),false);assert.equal(nodes.some(node=>node.props.accessibilityLabel==='Lihat lokasi laporan di Google Maps'),false);
 }
});

test('kegagalan membuka Google Maps memberi pesan pemulihan dan mempertahankan detail',async()=>{
 const view=historyView({openURL:async()=>{throw new Error('Tidak ada handler');}});
 view.render().find(node=>node.props.accessibilityLabel?.startsWith('Buka detail laporan')).props.onPress();view.render();await view.load();
 view.render().find(node=>node.props.accessibilityLabel==='Lihat lokasi laporan di Google Maps').props.onPress();await new Promise(resolve=>setImmediate(resolve));
 const nodes=view.render();assert.ok(nodes.some(node=>node.props.accessibilityRole==='alert'&&node.props.children.includes('Google Maps belum dapat dibuka')));assert.ok(nodes.some(node=>node.props.point));
});
