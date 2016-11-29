var mainJs = mainJs || {};
mainJs.plug = (function(){
	var change = function(options){
			var ops = options || {};
			$(ops.tabTargs).removeClass(ops.currentClass);
			$(ops.obj).addClass(ops.currentClass);
			var showId = $(ops.obj).attr("id");
			if(ops.opacity){
				$(ops.content).parent().find("."+ops.currentClass).stop();
				$("."+showId).stop();
				$(ops.content).parent().find("."+ops.currentClass).fadeOut("fast");
				$(ops.content).removeClass(ops.currentClass);
				$("."+showId).addClass(ops.currentClass).fadeIn("fast");
			}else{
				$(ops.content).hide();
				$("."+showId).show();
			}
		},
		autoChang = function(options,index,len){
			var ops = options || {};
			ops.obj = $(ops.tabTargs).eq(index);
			change(ops);			
		}
	return {
		tabs : function(options){
			var ops = options || {};
			var timer,
				index = 0,
				len = $(ops.tabTargs).length,
				delay = ops.delay || 3000,
				evt = ops.evt || "mouseover";
			if(evt == "click"){
				$(ops.tabTargs).bind("click",function(){
					clearInterval(timer);
					var obj = $(this);
					ops.obj = obj;
					change(ops);				
				});
			}else{
				$(ops.tabTargs).bind("mouseover",function(){
					clearInterval(timer);
					var obj = $(this);
					ops.obj = obj;
					change(ops);				
				});
				$(ops.tabTargs).bind("mouseout",function(){
					ops.nav && $(ops.content).hide();
				});
			}
			
			if(ops.autoPlay){
				timer = setInterval(function(){
					index ++;
					if(index >= len){
						index = 0;
					}
					autoChang(ops,index,len);
				},delay);
				$(ops.tabTargs).bind("mouseout",function(){
					index = parseInt($(this).text())-1;
					timer = setInterval(function(){
						index ++;
						if(index >= len){
							index = 0;
						}
						autoChang(ops,index,len);
					},delay);
				});
			}
		},
		slide : function(options){
			var ops = options || {};
			$(ops.pageBtn).eq(0).addClass(ops.currentClass).css("opacity",1);
			var slideTimer,
				slideIndex = 0,				
				slideLen = $(ops.pageBtn).length,
				perMoveWidth = ops.perMoveWidth || $(ops.content).parent().outerWidth(),
				slideDelay = ops.slideDelay || 3000,
				moveTime = ops.moveTime || 500;
			$(ops.pageBtn).bind("mouseover",function(){
				$(ops.content).stop();
				var tarNum = parseInt($(this).text()) - 1,
					moveWidth = (tarNum * perMoveWidth) + "px";
				$(ops.pageBtn).css("opacity",0.3);
				$(this).css("opacity",1);
				$(ops.pageBtn).removeClass(ops.currentClass);
				$(this).addClass(ops.currentClass);
				!($(ops.content).is(":animated")) && $(ops.content).animate({"left":"-"+moveWidth},moveTime);
			});
			if(ops.autoSlide){
				slideTimer = setInterval(function(){
					slideIndex ++;
					if(slideIndex >= slideLen){
						slideIndex = 0;
					}
					$(ops.pageBtn).css("opacity",0.3);
					$(ops.pageBtn).eq(slideIndex).css("opacity",1);
					$(ops.pageBtn).removeClass(ops.currentClass);
					$(ops.pageBtn).eq(slideIndex).addClass(ops.currentClass);
					!($(ops.content).is(":animated")) && $(ops.content).animate({"left":"-"+(slideIndex * perMoveWidth)+"px"},moveTime);
				},slideDelay);
				$(ops.pageBtn).bind("mouseout",function(){
					slideIndex = parseInt($(this).text())-1;
					slideTimer = setInterval(function(){
						slideIndex ++;
						if(slideIndex >= slideLen){
							slideIndex = 0;
						}
						$(ops.pageBtn).css("opacity",0.3);
						$(ops.pageBtn).eq(slideIndex).css("opacity",1);
						$(ops.pageBtn).removeClass(ops.currentClass);
						$(ops.pageBtn).eq(slideIndex).addClass(ops.currentClass);
						!($(ops.content).is(":animated")) && $(ops.content).animate({"left":"-"+(slideIndex * perMoveWidth)+"px"},moveTime);
					},slideDelay);
				});
			
				$(ops.content).parent().mouseenter(function(){
					clearInterval(slideTimer);
				}).mouseleave(function(){
					slideIndex = parseInt($(this).siblings(".footSlideNum").find("a.current").text())-1;
					slideTimer = setInterval(function(){
							slideIndex ++;
							if(slideIndex >= slideLen){
								slideIndex = 0;
							}
							$(ops.pageBtn).css("opacity",0.3);
							$(ops.pageBtn).eq(slideIndex).css("opacity",1);
							$(ops.pageBtn).removeClass(ops.currentClass);
							$(ops.pageBtn).eq(slideIndex).addClass(ops.currentClass);
							!($(ops.content).is(":animated")) && $(ops.content).animate({"left":"-"+(slideIndex * $(ops.content).parent().outerWidth())+"px"},moveTime);
						},slideDelay);
				});
			}
		},
		scroll : function(options){
			var ops = options || {};
			var scrollBtn = ops.scrollBtn,
				scrollCon = ops.scrollCon,
				speed = ops.speed || 300,
				moveWidth = ops.moveWidth;
			var showWidth = $(scrollCon).parent().outerWidth(),
				perWidth = $(scrollCon).find(".item").eq(0).outerWidth(),
				showNum = Math.round(showWidth / perWidth),
				allNum = $(scrollCon).find(".item").length,
				hideNum = allNum - showNum;
				minLeft = -(hideNum * perWidth);
			if(hideNum > 0){
				$(scrollCon).siblings(".next").addClass("nextBg");
			}
			$(scrollBtn).bind("click",function(){
				var ps =  $(scrollCon).position().left;
				if($(this).hasClass("pre")){
					if(ps < 0){
						!($(scrollCon).is(":animated")) && $(scrollCon).animate({"left" : (ps + moveWidth) + "px"},speed);
						$(scrollCon).siblings(".next").addClass("nextBg");
					}
					setTimeout(function(){
						var lPs = $(scrollCon).position().left;
						if(lPs == 0){
							$(scrollCon).siblings(".pre").removeClass("preBg");
						}
					},speed);
				}else if($(this).hasClass("next")){
					if(ps > minLeft){
						!($(scrollCon).is(":animated")) && $(scrollCon).animate({"left" : (ps - moveWidth) + "px"},speed);
						$(scrollCon).siblings(".pre").addClass("preBg");
					}
					setTimeout(function(){
						var rPs = $(scrollCon).position().left;
						if(rPs == minLeft){
							$(scrollCon).siblings(".next").removeClass("nextBg");
						}
					},speed);
				}
			});
		},
		dialog : function(options){
			var ops = options || {};
			var showBtn = ops.showBtn,
				closeBtn = ops.closeBtn,
				overlay = ops.overlay,
				showWin = ops.showWin;
			if(showBtn){
				$(showBtn).bind("click",function(){
					$(showWin).show();
					$(overlay).show();
				});
			}else{
				$(showWin).show();
				$(overlay).show();
			}			
			$(closeBtn).bind("click",function(){
                                $("#userNameSales").val("");
                                $("#code").val("");
                                $("#tel").val("");
                                $("#email").val("");
                                $("#company").val("");
				$(showWin).hide();
				$(overlay).hide();
                                location.reload();
			});
		},
		dropList : function(options){
			var ops = options || {};
			var dropBtn = ops.dropBtn,
				dropBtnClass = ops.dropBtnClass,
				dropInput = ops.dropInput,
				dropDownList = ops.dropDownList;
			$(dropBtn).bind("click",function(){
				$(dropDownList).slideToggle();
			});
			$(dropDownList).find("li").bind("click",function(){
				var selectText = $(this).text();
				$(dropInput).val(selectText);
				$(dropDownList).hide();
				$(dropBtn).removeClass(dropBtnClass);
			});
		},
		formatInput : function(options){
			var ops = options || {};
			var ipt = ops.ipt,
				fClass = ops.fClass;			
			$(ipt).focus(function(){				
				$(this).addClass(fClass);
				$(this).css("color","#333");
				var fVal = $(this).attr("val");
				var tVal =  $.trim($(this).val());
				if(tVal == fVal){
					$(this).val("");
				}
			}).blur(function(){
				$(this).removeClass(fClass);
				var fVal = $(this).attr("val");
				var tVal =  $.trim($(this).val());
				//alert(fVal==tVal);
				if(tVal == ""||fVal==tVal){
					$(this).css("color","#B4B7BB");
					$(this).val(fVal);
					$(this).attr("val",fVal);
				}else{
					$(this).css("color","#333");
				}
			});
		}
	}
})();