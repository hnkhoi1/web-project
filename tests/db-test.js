const fs=require('fs'); const store={};
global.localStorage={getItem:k=>k in store?store[k]:null,setItem:(k,v)=>{store[k]=String(v)},removeItem:k=>{delete store[k]}};
global.window=global; 
const src=fs.readFileSync('js/db.js','utf8')+';global.DB=DB;global.DEMO_USER=DEMO_USER;global.Session=Session;';
eval(src);
const ok=(c,m)=>console.log((c?'PASS ':'FAIL ')+m);
(async()=>{
  ok((await DB.login('demo','12345'))?.id==='demo','đăng nhập demo/12345');
  ok(!(await DB.login('demo','sai')),'sai mật khẩu bị từ chối');
  ok((await DB.login('DEMO ','12345'))?.id==='demo','username không phân biệt hoa thường');
  const r=await DB.createUser({email:'A@x.com',displayName:'An',password:'abc12345'});
  ok(r.user&&!r.user.verified,'đăng ký tài khoản mới (chưa kích hoạt)');
  ok((await DB.createUser({email:'a@x.com',displayName:'B',password:'abc12345'})).error==='EMAIL_EXISTS','trùng email bị chặn');
  ok((await DB.login('a@x.com','abc12345'))?.id===r.user.id,'đăng nhập tài khoản mới bằng email');
  ok(JSON.parse(store.hs_demo_db_v1).users[0].passHash!=='abc12345','mật khẩu được băm, không lưu thô');
  let threw=0; for(const f of [()=>DB.updateUser('demo',{displayName:'X'}),()=>DB.deleteUser('demo'),()=>DB.changePassword('demo','12345','abcd1234'),()=>DB.setPassword('demo','abcd1234')]){ try{await f()}catch{threw++} }
  ok(threw===4,'mọi thao tác sửa/xóa/đổi mật khẩu tài khoản demo đều bị từ chối');
  ok(Object.isFrozen(DEMO_USER)&&DEMO_USER.password==='12345','DEMO_USER đóng băng, không đổi');
  DB.saveNote(r.user.id,{title:'n1',content:'x'}); DB.saveNote('demo',{title:'d1',content:'y'});
  DB.deleteUser(r.user.id);
  ok(!DB.getUser(r.user.id)&&DB.listNotes(r.user.id).length===0,'xóa tài khoản kèm ghi chú');
  ok(DB.listNotes('demo').length===1,'ghi chú của demo không bị ảnh hưởng');
  await DB.createUser({email:'b@x.com',displayName:'Bình',password:'abc12345'}); DB.resetAddedUsers();
  ok(DB.users().length===1&&DB.users()[0].id==='demo'&&DB.listNotes('demo').length===1,'reset database: chỉ còn tài khoản demo');
  const p=await DB.createUser({email:'c@x.com',displayName:'C',password:'abc12345'}); 
  ok(await DB.changePassword(p.user.id,'abc12345','xyz98765')&&(await DB.login('c@x.com','xyz98765')),'đổi mật khẩu tài khoản mới');
})();
