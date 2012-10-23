var selektion;

function insertAtCursor(myField, myValue, bbCode1, bbCode2, endOfLine) {
var bbb;
bbCode1 = trimTxt(bbCode1);//added by MG
if(bbCode1=='[url=null]') { bbCode1=''; bbCode2=''; }
if(bbCode1=='[imgs]' && myValue==null) { bbCode1=''; bbCode2=''; myValue=''; }
if(bbCode1=='[img]null') { bbCode1=''; bbCode2=''; myValue=''; }//added by MG

if(bbCode1=='[imgs=null]') { bbCode1=''; bbCode2=''; myValue=''; }
if(bbCode2=='null[/imgs]') { bbCode2='[/imgs]'; myValue=''; }

if (document.selection) {
//IE support
//var str = document.selection.createRange().text;
myField.focus();
sel = document.selection.createRange();
sel.text = bbCode1 + myValue + bbCode2 + endOfLine;
if(myValue=='') {
bbb=bbCode2.length;
if(bbCode1.substring(0,4)=='[img' ) bbb=0; else bbb=-bbb;
sel.moveStart('character',bbb); sel.moveEnd('character',bbb);
}
sel.select();
return;
}
//MOZILLA/NETSCAPE support
else if (myField.selectionStart || myField.selectionStart == '0') {
var startPos = myField.selectionStart;
var endPos = myField.selectionEnd;
var bbb2, bbV, eoll;
if(myValue=='') myValue = myField.value.substring(startPos, endPos);
myField.value = myField.value.substring(0, startPos) + bbCode1 + myValue + bbCode2 + endOfLine + myField.value.substring(endPos, myField.value.length);
if(myValue=='') {

if(bbCode1.substring(0,4)=='[img' ){
bbb=bbCode1.length + myValue.length + bbCode2.length;
myField.selectionStart=startPos+bbb; myField.selectionEnd=startPos+bbb;
}
else{
bbb=bbCode1.length;
myField.selectionStart=startPos+bbb;
myField.selectionEnd=endPos+bbb;
}

}
else {
bbb=bbCode1.length;
bbb2=bbCode2.length;
bbV=myValue.length;
eoll=endOfLine.length;
myField.selectionStart=startPos+bbV+bbb+bbb2+eoll;
myField.selectionEnd=myField.selectionStart;
}
myField.focus();
myField.scrollTop=myField.scrollHeight;

return;
} else {
myField.value += myValue;
return;
}
}

function paste_strinL(strinL, isQuote, bbCode1, bbCode2, endOfLine){ 
if((isQuote==1 || isQuote==2) && strinL=='') alert(l_quoteMsgAlert);
else{
if (isQuote==1) {
bbCode1='[i]'; bbCode2='[/i]'; endOfLine='\n';
}
if (isQuote==2) {
bbCode1='[b]'; bbCode2='[/b]'; endOfLine='\n';
}
var isForm=document.getElementById('postMsg');
if (isForm) {
var input=document.getElementById('postText');
//var input=document.forms["postMsg"].elements["postText"];
insertAtCursor(input, strinL, bbCode1, bbCode2, endOfLine);
}
else alert(l_accessDenied);
}
}

function pasteSel() {
selektion='';
if(window.getSelection) {
this.thisSel=window.getSelection()+'';
selektion=this.thisSel.toString();
}
else if(document.getSelection) selektion=document.getSelection()+'';
else if(document.selection) selektion=document.selection.createRange().text;
}


function trimTxt(s) {
while (s.substring(0,1) == ' ') {
s = s.substring(1,s.length);
}
while (s.substring(s.length-1,s.length) == ' ') {
s = s.substring(0,s.length-1);
}
return s;
}

function submitForm(){
var pf=document.forms['postMsg'];
var ftitle=false, ftext=false, flogin=false, fpass=false, user_usr='', user_pwd='', topicTitle='', postText='', fsubmit=true;
if(pf.elements['user_usr']) { flogin=true; user_usr=trimTxt(pf.elements['user_usr'].value); }
if(pf.elements['user_pwd']) { fpass=true; user_pwd=trimTxt(pf.elements['user_pwd'].value); }
if(pf.elements['postText']) { ftext=true; postText=trimTxt(pf.elements['postText'].value); }
if(pf.elements['topicTitle']) { ftitle=true; topicTitle=trimTxt(pf.elements['topicTitle'].value); }
if(pf.elements['CheckSendMail'] && pf.elements['CheckSendMail'].checked) { tlength=0; }

if(flogin && fpass && user_usr!='' && user_pwd!='') fsubmit=true;
else if(flogin && fpass && anonPost==0 && user_pwd=='') fsubmit=false;
else if(ftext && postText.length<tlength) fsubmit=false;
else if(ftitle && topicTitle.length<tlength) fsubmit=false;

if(fsubmit) { pf.elements['subbut'].disabled=true; document.forms['postMsg'].submit(); } else { alert(l_accessDenied); return; }
}


//preview 
function previewMessage(){
var modeChs;

var w = window.open('', 'cWin2', 'resizable=yes, scrollbars=yes, width=600, height=400, top=0, left=300');
if (w.focus)w.focus();
document.forms['postMsg'].target='cWin2';
document.forms['postMsg'].elements['prevForm'].value=1;
if(document.forms['postMsg'].elements['mode']) {
document.forms['postMsg'].elements['mode'].value='';
modeChs=true;
}
var prevEnc=document.forms['postMsg'].encoding;
if(prevEnc=='multipart/form-data') document.forms['postMsg'].encoding='application/x-www-form-urlencoded';
document.forms['postMsg'].submit();
document.forms['postMsg'].target='_self';
if(prevEnc=='multipart/form-data') document.forms['postMsg'].encoding=prevEnc;
document.forms['postMsg'].elements['prevForm'].value=0;
if(modeChs) document.forms['postMsg'].elements['mode'].value='login';
return;
}
//eof preview

//insert image
function showInsertImage(){
if(e = document.getElementById('insert_image')){
e.style.display = e.style.display == '' ? 'none' : '';	
}
}
function onSelectImgInsert(e){
document.getElementById('img_insert').value = (parseInt(e.value) == 0) ? '' : 'http://';
}
function doInsertImage(){
var val = document.getElementById('img_insert').value;
if(parseInt(document.getElementById('option_insert').value) == 0){
//woophy image
if(!isNaN(parseInt(val))){
jQuery.get(window.root_url+'services?method=woophy.photo.getUrl&photo_id='+val+'&size=l', onGetUrl);
}else showInsertImage(false);
}else{
paste_strinL('', 4, '[img='+document.getElementById('img_insert').value+']', '[/img]', '');
showInsertImage(false);
}
}
function onGetUrl(xml, success){
try{
showInsertImage(false);
paste_strinL('', 4, '[img='+xml.getElementsByTagName('url').item(0).firstChild.data+']', '[/img]', '');
}catch(error){
alert('Photo not found!');
};
}
//eof insert image