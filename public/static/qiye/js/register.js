var timer;
function registToSale() {
	var userNameSales= document.getElementById("userNameSales").value;
	var code = document.getElementById("code").value;
	var tel = document.getElementById("tel").value;
	var email = document.getElementById("email").value;
	var company = document.getElementById("company").value;
	if(code=='区号'){code='';}
      //  $(".overlay").show();
	$.ajax({
		type : 'GET',
		async : false,
		cache: false,
		contentType:"application/x-javascript;charset=utf-8",
		url : "http://www.163em.com/qiye/save.asp",
		dataType : "jsonp",
		jsonp : "callbackparam",//服务端用于接收callback调用的function名的参数
		jsonpCallback : "success_registCallback",//callback的function名称
		data : {
			"action" :'save',
			"Name" :userNameSales,
			"Phone" : code+tel,
			"Email": email,
			"Company":company,
			"requesturl":window.location.href
		},
		beforeSend : function() {
		},
		success : function(json) {
		},
		error : function() {
			//$(".server_error").show();
		}
	});
}
function success_registCallback(data) {
	var info=data[0].info;
	if(info=="success"){
		$(".try_box").show();
		$(".overlay").show();
	}else if(info=="repeat"){
		$(".server_error").empty();
                $(".server_error").append(data[1].error);
                $(".server_error").show();
                $(".overlay").hide();
	}else{
		$(".server_error").empty();
            $(".server_error").append("（服务器异常，请稍后重试）");
            $(".server_error").show();
            $(".overlay").hide();
	}
	
}
function emailCheck(element){
	var email = document.getElementById(element).value;
	invalidChars = " /;,:{}[]|*%$#!()`<>?";
	if (email == ""){
		return false;
	}
	for (i=0; i< invalidChars.length; i++){
		badChar = invalidChars.charAt(i) ;
		if (email.indexOf(badChar,0) > -1) {
			return false;
		}
	}
	atPos = email.indexOf("@",1) ;
	if (atPos == -1) {
		return false;
	}
	if (email.indexOf("@", atPos+1) != -1){
		return false;
	}
	periodPos = email.indexOf(".",atPos) ;
	if(periodPos == -1) {
		return false;
	}
	if ( atPos +2 > periodPos) {
		return false;
	}
	if ( periodPos +3 > email.length) { return false; }
		return true;
}
