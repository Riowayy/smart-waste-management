const {test}=require('node:test');
const assert=require('node:assert/strict');
const fs=require('node:fs');
const vm=require('node:vm');
const ts=require('typescript');
function setup(os='web',request=async()=>({data:{id:7}}),Body=FormData) {
 const source=ts.transpileModule(fs.readFileSync('src/features/reporting/service.ts','utf8'),{compilerOptions:{module:ts.ModuleKind.CommonJS}}).outputText;
 // Transpiled module references FormData from its VM global.
 const context={module:{exports:{}},FormData:Body,Blob};
 const typesModule={exports:{}};
 const typesSource=ts.transpileModule(fs.readFileSync('src/services/api/types.ts','utf8'),{compilerOptions:{module:ts.ModuleKind.CommonJS}}).outputText;
 vm.runInNewContext('(function(module,exports){'+typesSource+'})')(typesModule,typesModule.exports);
 class NativeFile {
  constructor(uri){this.uri=uri;this.name=uri.split('/').pop();this.type='image/jpeg';}
  async bytes(){return new Uint8Array([255,216,255,217]);}
 }
 vm.runInNewContext('(function(require,module,exports){'+source+'})',context)(name=>name==='react-native'?{Platform:{OS:os}}:name==='expo-file-system'?{File:NativeFile}:{apiClient:{request},ApiError:typesModule.exports.ApiError},context.module,context.module.exports);
 return context.module.exports;
}
const photo={uri:'file:///photo.jpg',mimeType:'image/jpeg',fileSize:123};
const draft={description:'Temuan sampah',categoryId:null,photo,location:{latitude:-6.2,longitude:106.8}};
test('validasi wajib, batas ukuran/format dan koordinat',()=>{
 const s=setup();
 assert.ok(s.validateDraft({...draft,description:' ',photo:null}).image);
 assert.equal(s.validatePhoto({...photo,fileSize:5*1024*1024}),null);
 assert.ok(s.validatePhoto({...photo,fileSize:5*1024*1024+1}));
 assert.ok(s.validatePhoto({...photo,mimeType:'image/heic'}));
 assert.ok(s.validateDraft({...draft,location:{latitude:NaN,longitude:0}}).latitude);
 assert.ok(s.validateDraft({...draft,location:{latitude:91,longitude:0}}).latitude);
 assert.ok(s.validateDraft({...draft,location:{latitude:0,longitude:181}}).latitude);
});
test('deskripsi 1.000 karakter diterima dan 1.001 ditolak sebelum multipart dibuat',()=>{
 const s=setup('android',undefined,class { values={}; append(k,v){this.values[k]=v;} });
 const valid={...draft,description:'x'.repeat(1000)};
 const invalid={...draft,description:'x'.repeat(1001)};
 assert.equal(s.validateDraft(valid).description,undefined);
 assert.equal(s.createReportBody(valid).values.description.length,1000);
 assert.equal(s.validateDraft(invalid).description[0],'Deskripsi wajib diisi, maksimal 1.000 karakter.');
 assert.throws(()=>s.createReportBody(invalid),/Periksa data laporan/);
});
test('multipart Web membawa File dan nilai nol koordinat, tanpa field pemilik/status',()=>{
 const s=setup();
 const file=new File(['foto'],'photo.png',{type:'image/png'});
 const body=s.createReportBody({...draft,categoryId:3,photo:{...photo,file},location:{latitude:0,longitude:0}});
 assert.equal(body.get('image').name,'photo.png');
 assert.equal(body.get('category_id'),'3');
 assert.equal(body.get('latitude'),'0');
 assert.equal(body.has('user_id'),false);
 assert.equal(body.has('status'),false);
});
test('kategori opsional tidak dikirim dan POST melalui client privat',async()=>{
 const file=new File(['foto'],'photo.png',{type:'image/png'});
 const s=setup('web',async(path,options)=>{
  assert.equal(path,'reports');assert.equal(options.method,'POST');
  assert.equal(options.body.has('latitude'),true);assert.equal(options.body.has('longitude'),true);assert.equal(options.body.has('category_id'),false);
  assert.notEqual(options.authenticated,false);return {data:{id:7}};
 });
 assert.equal((await s.submitReport({...draft,photo:{...photo,file}})).id,7);
});
test('koordinat opsional sesuai kontrak API, tidak dikirim bila tidak tersedia',()=>{
 const s=setup('android',undefined,class { values={}; append(k,v){this.values[k]=v;} });
 assert.equal(s.validateDraft({...draft,location:null}).latitude,undefined);
 assert.equal(s.createReportBody({...draft,location:null}).values.latitude,undefined);
});
test('kategori berasal dari API dan error upload diteruskan',async()=>{
 const error=new Error('HTTP 422');
 const s=setup('web',async(path)=>{assert.equal(path,'categories');throw error;});
 await assert.rejects(s.getCategories(),e=>e===error);
});

test('multipart native memakai File berbasis URI dengan MIME, nama, dan bytes',()=>{
 class NativeBody { values={}; append(k,v){this.values[k]=v;} }
 const s=setup('android',undefined,NativeBody);
 const body=s.createReportBody(draft);
 assert.equal(body.values.image.uri,photo.uri);
 assert.equal(body.values.image.type,'image/jpeg');
 assert.equal(body.values.image.name,'photo.jpg');
 assert.equal(typeof body.values.image.bytes,'function');
});

test('ukuran foto non-finite ditolak',()=>{
 const s=setup();
 for(const fileSize of [NaN,Infinity,-Infinity]) assert.ok(s.validatePhoto({...photo,fileSize}));
});
test('format yang diizinkan dan batas koordinat tetap diterima',()=>{
 const s=setup();
 for(const mimeType of ['image/jpeg','image/png','image/webp']) assert.equal(s.validatePhoto({...photo,mimeType}),null);
 for(const [latitude,longitude] of [[-90,-180],[90,180],[0,0]]) {
  assert.equal(Object.keys(s.validateDraft({...draft,location:{latitude,longitude}})).length,0);
 }
});
test('respons sukses tanpa ID tidak boleh dianggap laporan terkirim',async()=>{
 const s=setup('web',async()=>({data:{}}));
 const file=new File(['foto'],'photo.png',{type:'image/png'});
 await assert.rejects(s.submitReport({...draft,photo:{...photo,file}}),e=>e.kind==='invalid-response');
});

test('reproduksi encoder Expo SDK 58: URI descriptor gagal, File bytes berhasil menjadi multipart',async()=>{
 const filename='node_modules/expo/src/winter/fetch/convertFormData.ts';
 const moduleValue={exports:{}};
 const source=ts.transpileModule(fs.readFileSync(filename,'utf8'),{compilerOptions:{module:ts.ModuleKind.CommonJS,target:ts.ScriptTarget.ES2022}}).outputText;
 vm.runInNewContext('(function(require,module,exports){'+source+'})',{Blob,Uint8Array,TextEncoder})(()=>({blobToArrayBufferAsync:blob=>blob.arrayBuffer()}),moduleValue,moduleValue.exports);
 class Body {constructor(){this.values={};}append(k,v){this.values[k]=v;}entries(){return Object.entries(this.values);}}
 const legacy=new Body();legacy.append('image',{uri:photo.uri,name:'photo.jpg',type:'image/jpeg'});
 await assert.rejects(moduleValue.exports.convertFormDataAsync(legacy,'boundary'),/Unsupported FormDataPart implementation/);
 const s=setup('android',undefined,Body),body=s.createReportBody(draft);
 const encoded=await moduleValue.exports.convertFormDataAsync(body,'boundary');
 const text=new TextDecoder('latin1').decode(encoded.body);
 assert.ok(text.includes('name="image"; filename="photo.jpg"'));
 assert.ok(text.includes('Content-Type: image/jpeg')||text.includes('content-type: image/jpeg'));
 assert.ok(encoded.body.some(byte=>byte===255));
});
