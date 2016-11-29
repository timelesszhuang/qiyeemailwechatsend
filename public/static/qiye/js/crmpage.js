$(document).ready(function(){
		var tabsArray = [
			{ 
				tabTargs : $(".slideNum a"),
				currentClass : "current",
				content : $(".banner_ul li"),
					autoPlay : true,
				delay : 10000
			},
			{
				tabTargs : $(".function_ul li"),
				currentClass : "current",
				content : $(".container")
			},
			{
				tabTargs : $(".user_ul li"),
				currentClass : "selected",
				content : $(".userContainer")
			},
			{
				tabTargs : $(".user_ul li"),
				currentClass : "",
				content : $(".user_select")
			}
		];
		dialogArray = [
			{
				showBtn : $("xx"),
				closeBtn : $(".try_close"),
				overlay : $(".overlay"),
				showWin : $(".try_box")
			},
			{
				showBtn : $("xx"),
				closeBtn : $(".returnZY"),
				overlay : $(".overlay"),
				showWin : $(".try_box")
			}
		];
		scrollArray = [//
			{
				scrollBtn : $(".wrapBox .scrollBtn"),
				scrollCon : $(".scrollBox .scrollCon"),
				moveWidth : ($(".scrollBox .scrollCon .item").eq(0).outerWidth()) * 3,
				speed : 1000
			}
		];
		iptArray = [
			{
				ipt : $("input.try_blur"),
				fClass : "topUp_input_focus"
			},
			{
				ipt : $("input.try_code"),
				fClass : "topUp_input_focus"
			}
		];
		for(var i=0,len = tabsArray.length;i<len;i++){
			mainJs.plug.tabs(tabsArray[i]);
		} 
		for(var m=0,dLen=dialogArray.length;m<dLen;m++){
			mainJs.plug.dialog(dialogArray[m]);
		}
		for(var k=0,sLen=scrollArray.length;k<sLen;k++){
			mainJs.plug.scroll(scrollArray[k]);
		}
		for(var p=0,ilen = iptArray.length;p<ilen;p++){
			mainJs.plug.formatInput(iptArray[p]);
		}
		$(".function_ul li").bind("click",function(){
			$('html,body').animate({scrollTop: '400px'}, 200);
		});
		

		$(".navBox li").bind("click",function(){
			$(".navBox li").removeClass("selected");
			$(this).addClass("selected");
			var id=$(this).attr("id");
			if(id=="shouye"){
				$('html,body').animate({scrollTop: '0px'}, 200);
			}else if(id=="sub_pro"){
				$('html,body').animate({scrollTop: '400px'}, 200);
			}else if(id=="sub_buy"){
				$('html,body').animate({scrollTop: '990px'}, 200);
			}else if(id=="sub_customer"){
				$('html,body').animate({scrollTop: '1510px'}, 200);
			}
			else if(id=="sub_customer1"){
				$('html,body').animate({scrollTop: '1980px'}, 200);
			}
		});

    $(window).scroll(function(){
        var scrollTop=document.body.scrollTop||document.documentElement.scrollTop;
		if(scrollTop<350){//��ҳ
			$(".navBox li").removeClass("selected");
			$("#shouye").addClass("selected");
		}else if(350<scrollTop&&scrollTop<870){//��Ʒ����
            $(".navBox li").removeClass("selected");
			$("#sub_pro").addClass("selected");
        }else if(870<scrollTop&&scrollTop<1470){//��Ʒ����
			$(".navBox li").removeClass("selected");
			$("#sub_buy").addClass("selected");
		}
		else if(870<scrollTop&&scrollTop<1510){//��Ʒ����
			$(".navBox li").removeClass("selected");
			$("#sub_customer").addClass("selected");
		}
		
		else{//�ͻ�����
			$(".navBox li").removeClass("selected");
			$("#sub_customer1").addClass("selected");
		}
		
		
    });
	//������ñ?��֤
	$(".try_blur").blur(function(){
		//var telReg = /^[0-9-()]+$/,
		var telReg = /^((\d{3}-\d{8})|(\d{4}-\d{7}))$/,
			mobileReg = /^1[3|4|5|8][0-9]\d{8}$/,
			mailReg = /^[A-Za-z0-9]+([-_.]\w+)*@[\w-]+(\.[\w-]+)+$/; 
			lenReg = /^[a-zA-Z0-9-����.()����\u4e00-\u9fa5]{2,50}$/;; 
		var reMsg = $(this).attr("reMsg"),
			errorMsg = $(this).attr("errorMsg"),
			errorMsgTel = $(this).attr("errorMsgTel"),
			errorMsgMobile = $(this).attr("errorMsgMobile"),
			val = $(this).attr("val"),
			try_code = $.trim($(".try_code").val().replace($(".try_code").attr("val"),"")),
			thisVal = $(this).val();
			//alert(try_code);
			//alert(thisVal);
		if(val == thisVal || $.trim(thisVal) == ""){
			$(this).siblings(".hint_phone").html(reMsg);
			//$(this).siblings("span.hint_phone").attr("style","display:block");
			$(this).siblings("a.validate").attr("style","display:block");
			$(this).siblings("a.validate").removeClass("yes");
			$(this).siblings("a.validate").addClass("close");
			//alert(1);
		}else if(($(this).attr("name") == "userNameSales") || ($(this).attr("name") == "company")){
			if(!lenReg.test(thisVal)){
				($(this).attr("name") == "userNameSales") && ($(this).siblings(".hint_phone").html("��ϵ�����벻��ȷ"));
				($(this).attr("name") == "company") && ($(this).siblings(".hint_phone").html("��˾���벻��ȷ"));
				$(this).siblings("a.validate").attr("style","display:block");
				$(this).siblings("a.validate").removeClass("yes");
				$(this).siblings("a.validate").addClass("close");
			}else{
				$(this).siblings(".hint_phone").html("");
				$(this).siblings(".hint_phone").hide();
				$(this).siblings("a.validate").attr("style","display:block");
				$(this).siblings("a.validate").removeClass("close");
				$(this).siblings("a.validate").addClass("yes");
			}
		}else if($(this).hasClass("tel")){
			if(try_code != ""){
				var telNum = try_code + "-" + thisVal;
				if(!telReg.test(telNum)){
					$(this).siblings(".hint_phone").html(errorMsgTel);
					$(this).siblings("a.validate").attr("style","display:block");
					$(this).siblings("a.validate").removeClass("yes");
					$(this).siblings("a.validate").addClass("close");
				}else{
					$(this).siblings(".hint_phone").html("");
					$(this).siblings(".hint_phone").hide();
					$(this).siblings("a.validate").attr("style","display:block");
					$(this).siblings("a.validate").removeClass("close");
					$(this).siblings("a.validate").addClass("yes");
				}
			}else if(try_code == ""){
				if(!mobileReg.test(thisVal)){
					$(this).siblings(".hint_phone").html(errorMsgMobile);
					$(this).siblings("a.validate").attr("style","display:block");
					$(this).siblings("a.validate").removeClass("yes");
					$(this).siblings("a.validate").addClass("close");
				}else{
					$(this).siblings(".hint_phone").html("");
					$(this).siblings(".hint_phone").hide();
					$(this).siblings("a.validate").attr("style","display:block");
					$(this).siblings("a.validate").removeClass("close");
					$(this).siblings("a.validate").addClass("yes");
				}
			}
		}else if($(this).hasClass("email")){
			if(!mailReg.test(thisVal)){
				$(this).siblings(".hint_phone").html(errorMsg);
				$(this).siblings("a.validate").attr("style","display:block");
				$(this).siblings("a.validate").removeClass("yes");
				$(this).siblings("a.validate").addClass("close");
			}else{
				$(this).siblings(".hint_phone").html("");
				$(this).siblings(".hint_phone").hide();
				$(this).siblings("a.validate").attr("style","display:block");
				$(this).siblings("a.validate").removeClass("close");
				$(this).siblings("a.validate").addClass("yes");
			}
		}else{
			$(this).siblings(".hint_phone").html("");
			$(this).siblings(".hint_phone").hide();
			$(this).siblings("a.validate").attr("style","display:block");
			$(this).siblings("a.validate").removeClass("close");
			$(this).siblings("a.validate").addClass("yes");
		}
	});
	$(".validate").bind("click",function(){
		if($(this).attr("class")=="validate close"){
			$(this).siblings(".hint_phone").show();
			var attention=$(this).siblings(".hint_phone");
			var os = 3;
			var timer = setInterval(function(){
				os = parseInt(os) - 1;
				if(os == 0){
					clearInterval(timer);
					attention.hide();
					return false;
				}
			},1000);
		}
	});
	
	$(".button.submit").bind("click",function(){
		$(".try_blur").blur();
		$.each($("a.validate"),function(i,o){
			if($(o).attr("class")=="validate close"){
				$(o).click();
				return false;
			}
		});
		var errorLen = $(".close").length;
		if(errorLen == 0){
			registToSale();
		}
	});
})

