function ic_odswiezKapcze() {
 $.get(configRoot + "imgcaptcha_p.php", function(data) {
  $("#imgcaptcha_hash").val(data);
  $("#imgcaptcha_img").prop("src",configRoot + "imgcaptcha_im.php?cr=" + data);
 });
}
//function resetujKapcze() {
// $("#imgcaptcha_img").prop("src",configRoot + "zakrytek.png");
//}
//$(document).ready(function(){
// //resetujKapcze(); - to nie powinno byc na razie potrzebne
//});
