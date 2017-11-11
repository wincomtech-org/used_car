/*头部导航*/
	$('.nav_item ').hover(function(){
		$(this).addClass('active1');
		$(this).find('.nav_second_item').show();
	},function(){
		$(this).removeClass('active1');
		$(this).find('.nav_second_item').hide();
	})

	/***首页*/
	$(document).ready(function(){
			var $div_ul=$('.vehicle_con>ul')
			$('.vehicle_tit li').click(function(){

				var $t=$(this).index();

				$(this).addClass('active').siblings().removeClass('active');
				$div_ul.eq($t).show(600).siblings().hide(600);

			});

			/**预约看车*/
			var  $car_li=$('.car_process .car_process_list ');

			$('.car_process_tit_item').click(function(){


				/**其他*/
				$('.car_process_tit_item').each(function(){
			

					var $d=$(this).index();

					var img_src=$(this).find('img').attr('src').split(".");

					var img_=$(this).find('img').attr('src').split(".")[0];

					var img__=img_.split('_01')[0];
				
					if(img_.indexOf('_01') > -1 ){
						
						$(this).find('img').attr('src' , img__+"."+img_src[1]);
						
					}	

				})

				var $d=$(this).index();
				var $this_siblings=$(this).siblings();
				var img_src=$(this).find('img').attr('src').split(".");
				var img_=$(this).find('img').attr('src').split(".")[0];
				var img__=img_.split('_01')[0];
			
				if(img_.indexOf('_01') > -1 ){
					
					$(this).find('img').attr('src' , img__+"."+img_src[1]);
					
				}else{
					$(this).find('img').attr('src' , img_src[0]+"_01."+img_src[1]);
				}
			


				$(this).addClass('active').siblings().removeClass('active');			

				
				$car_li.eq($d).show(600).siblings().hide(600);
			})
	})




	/**车辆买卖*/

	$(document).delegate('.analogy_tit','click',function(e){
		
		$('.analogy_con').each(function(){
			$(this).hide();
		})


		$(this).siblings('.analogy_con').show();
		var _this=$(this);
		var _this_siblings=$(this).siblings('.analogy_con');
		var _parrent=$(this).parent().parent();
		var _this_siblings_li=$(this).siblings('.analogy_con').children('li');

		
		
		$(document).one('click',function(){
			_this_siblings.hide();
		})
		e.stopPropagation();

		_this_siblings_li.on('click',function(){
			var txt=$(this).children('input').val();
			
			_this.children('input').val(txt);
			
			_this_siblings.hide();

		})

	})
	

	// 验证手机号
	function isPhoneNo(phone) { 
		 var pattern = /^1[34578]\d{9}$/; 
		 return pattern.test(phone); 
	}

	// $('input[name="tel"]').blur(function(){
		
	// 	if(isPhoneNo($.trim($(this).val())) == false ){

	// 			$(this).parent().css('height','auto')
	// 			$(this).parent().siblings('i').show()
	// 		}else{
	// 			$(this).parent().css('height','35px')
	// 			$(this).parent().siblings('i').hide()
	// 		}
	// })

	$('.yuyue_guang').hover(function(){
		$(this).children('.yuyueguang').show();
	},function(){
		$(this).children('.yuyueguang').hide();
	})

	$('.yuyue_guang a').click(function(){
		$(this).html('400-2263-6547')
	})	

	$('.cycle_icon li').click(function(){

		$(this).addClass('active').siblings().removeClass('active');
		var t=$(this).index();
		$('.simila_recommendation_list ').animate({'margin-left':-t*100+"%"},600);

		
	})
		
	$(".car_message_nav_list").click(function(){
		$(".car_message_nav").css({"position":"fixed","top":"0","z-index":"1"});
		$(this).addClass('active').siblings().removeClass('active');
		var id=$(this).children('a').attr('href');
		console.log(1,id)
		
		$("html, body").animate({
            scrollTop: $(id).offset().top-50 }, {duration: 500,easing: "swing"});
      
	})	


	function scroll(){
		var oTop = $(".car_message_nav").offset().top ;
		var sTop = 0;
		$(window).scroll(function(){
			sTop = $(this).scrollTop();

			if(sTop >= oTop){
					$(".car_message_nav").css({"position":"fixed","top":"0","z-index":"1"});
			}else{
					$(".car_message_nav").css({"position":"static"});
			}
			$('.car_message_con_item').each(function(){
				var x=$(this).scrollTop();			
				var y=$(this).offset().top;	
			
				var off_top=$(this).offset().top;
				var off_top_cha=sTop - off_top;
				if( off_top_cha > 60){
					$()
				}
			})

		});
	}

		/*预约看车*/
	function car_mess_btn_submit(){
		
		if($.trim($('input[name="tel"]').val()) == "" || $.trim($('input[name="tel"]').val()) == "请输入电话号码" || isPhoneNo($.trim($('input[name="tel"]').val())) == false){
		 	alert('请填写正确手机号')
		 	$('input[name="tel"]').siblings('i').css('display','inline-block')
		 	return false;
		 }else if($.trim($('input[name="yanzhengma"]').val())== "" || $.trim($('input[name="yanzhengma"]').val())== "请输入验证码"){
		 	alert('输入验证码')
		 	$('input[name="yanzhengma"]').siblings('i').css('display','inne-block')
		 	return false;
		 }
	}
	// $('.yuyue_guang_mess_list input').blur(function(){

	// 	if($.trim($('input[name="tel"]').val()) == "" || $.trim($('input[name="tel"]').val()) == "请输入电话号码" || isPhoneNo($.trim($('input[name="tel"]').val())) == false){
	// 		$(this).siblings('i').css('display','inline-block');


	// 	}else{
	// 		$(this).siblings('i').css('display','none');

	// 	}
	// })	


	/*车辆买卖信息*/
	/*表单验证*/
	function check(){
			if($('input[name="brand"]').val() == "请选择品牌"){
				// $('input[name="brand"]').parent().parent().parent().css('height','auto')
				// $('input[name="brand"]').parent().parent().siblings('i').show();
				alert('请选择品牌')
				return false;
			}else if($('input[name="motorcycle"]').val() == "请选择车系"){
				// $('input[name="motorcycle"]').parent().parent().parent().css('height','auto')
				// $('input[name="motorcycle"]').parent().parent().siblings('i').show();
				alert('请选择车系')
				return false;
			}else if($('input[name="tel"]').val() == ""){
				// $('input[name="tel"]').parent().parent().parent().css('height','auto')
				// $('input[name="tel"]').parent().parent().siblings('i').show();
				alert('请填写手机号')
				return false;
			}
	}

	

		$('.vehiTrad_tit_item .more').on('click',function(){
			if($(this).hasClass('on')){
				$(this).removeClass('on');
				$(this).parent().parent().css('height','50px');
			}else{
				$(this).addClass('on');
				$(this).parent().parent().css('height','auto');
			}
				
		})

		$('.vehiTrad_tit_item_other_list p').on('click',function(e){
			var _this=$(this);
			var _this_siblings=$(this).siblings('ul');
			var _this_siblings_li=$(this).siblings('ul').children('li');

			_this_siblings.show();
				$(document).one('click',function(){
				_this_siblings.hide();
			})
			e.stopPropagation();

			_this_siblings_li.on('click',function(){
				var txt=$(this).children('a').text();
				_this.html(txt)
				_this_siblings.hide();

			})

		})


		/**车险服务 --投保流程*/
		$('.claim_guidance_guide li .circle').click(function(){
		var _parent=$(this).parent();
		var _t=_parent.index();
		var _div=$('.claim_guidance_guide_con_list');
		console.log(_t)
		_parent.addClass('active').siblings().removeClass('active');

		 _div.eq(_t).show().siblings().hide(600);
	})



	// 电话验证
     function isPhoneNo(phone){
     	var pattern=/(^(([0\+]\d{2,3}-)?(0\d{2,3})-)(\d{7,8})(-(\d{3,}))?$)|(^0{0,1}1[3|4|5|6|7|8|9][0-9]{9}$)/;
           return  pattern.test(phone)
     }

     // 车辆号牌
     function car_num(num){
     	var pattern=/^[京津沪渝冀豫云辽黑湘皖鲁新苏浙赣鄂桂甘晋蒙陕吉闽贵粤青藏川宁琼使领A-Z]{1}[A-Z]{1}[A-Z_0-9]{5}$/;
     	return pattern.test(num)
     }


	/*strat**险种选择，资料填写*/
	function is_submit(value){
		console.log($('input[name="sfz"]'))

		var form1 = $("#data_filling");
		// 在线投保
		if($.trim($('input[name="user_name"]').val()).length == 0){
		 	alert('请填写正确用户名')
		 	$('input[name="user_name"]').siblings('b').css('display','block')
		 	return false;
		 }else if($.trim($('input[name="tel"]').val()).length == 0|| isPhoneNo($.trim($('input[name="tel"]').val())) == false){
		 	alert('请填写正确手机号')
		 	$('input[name="tel"]').siblings('b').css('display','block')
		 	return false;
		 }else if($.trim($('input[name="car_num"]').val()).length ==  0 || car_num($.trim($('input[name="car_num"]').val())) == false){
		 	alert('请填写车牌号')
		 	$('input[name="car_num"]').siblings('b').css('display','block')
		 	return false;
		 }else if($.trim($('input[name="xcz"]').val()).length == 0){
		 	alert('请上传行车本照片')
		 	$('input[name="xcz"]').parent().parent().siblings('b').css('display','block')
		 	return false;
		 }else if($('input[name="sfz"]') && $.trim($('input[name="sfz"]').val()) == ""){
		 	alert('请上传身份证正面照片')
		 	$('input[name="sfz"]').parent().parent().siblings('b').css('display','block')
		 	return false;

		 }else{
		 	 if (value == 1) {		        	      
		            var id = $("#id").val();
		            form1.action = "/Test/querrybyid?id="+id;
		             $("#form1").attr("action",form1.action);
		            form1.submit();
		        }
		        // 线上投保
		        if (value == 2) {		        
		            var title = $("#title").val();
		            form1.action = "/Test/querrybyname?title=" + title;
		            $("#form1").attr("action", form1.action);
		            form1.submit();
		        }
		 }     
	}



	
	// $('.data_filling_list  .right input').blur(function(){
	// 	if($.trim($(this).val()) == ""){
	// 		$(this).siblings('b').css('display','block');
	// 		if($(this).attr('type') == 'file'){
	// 			$(this).parent().parent().siblings('b').hide();
	// 		}
	// 	}else if($(this).attr('name') == 'tel' && isPhoneNo($.trim($('input[name="tel"]').val())) == false){
	// 		$(this).siblings('b').css('display','block');


	// 	}else if($(this).attr('name') == 'car_num' && car_num($.trim($('input[name="car_num"]').val())) == false){
	// 		$(this).siblings('b').css('display','block');
	// 	}else{
	// 		$(this).siblings('b').css('display','none');
	// 		if($(this).attr('type') == 'file'){
	// 			$(this).parent().parent().siblings('b').show();
	// 		}
	// 	}
	// })

	$('input[type="file"]').change(function(){
	  	var x=$(this).attr('id');
	  	var y=$(this).parent().parent().siblings().find('.show_img')
	  	$(this).parent().parent().siblings('.img_div').show();
	  	$(this).parent().parent().siblings('b').hide();
	    var f = document.getElementById(x).files[0];
	    var src = window.URL.createObjectURL(f);
	    
	   $(this).parent().parent().siblings().find('.show_img').attr('src',src);

	})	

	/*end**险种选择，资料填写*/


	/*车险选择签合同 */
	$(".contract_p .check_span input").click(function(){
                       
                     
        if($(this).prop('checked')){
        	$(this).siblings('label').attr('tid','1')
        }else{
        	$(this).siblings('label').attr('tid','0')
        }
    })
	function contract(e){
		if($(".contract_p .check_span>label").attr('tid') != 1){
			alert("您未同意服务条款")
    		return false;
		}
		e.preventDefault();
	}

	/*同意服务*/
	$('.auto_login input').click(function(){
		               
	        if($(this).prop('checked')){
	        	$(this).parent('label').attr('tid','1')
	        }else{
	        	$(this).parent('label').attr('tid','0')
	        }
	})

	/****登录按钮**/
	function loginLayoutValidate(){
		if($.trim($('input[name="tel"]').val()).length == 0|| isPhoneNo($.trim($('input[name="tel"]').val())) == false){
		 	alert('请填写正确手机号')
		 	$('input[name="tel"]').siblings('b').css('display','block')
		 	return false;
		 }else if($.trim($('input[name="password"]').val()).length == 0){
		 	alert('请填写密码')
		 	$('input[name="password"]').siblings('b').css('display','block');
		 	return false;
		 }else if($.trim($('input[name="yanzheng"]').val()).length == 0){
		 	alert('请填写验证码')
		 	$('input[name="yanzheng"]').siblings('b').css('display','block');
		 	return false;
		 }else{
		 	if($(".register_form .auto_login label").attr('tid') != 1){
				alert("您未同意服务条款");
	    		return false;
			}
		 }
	}

// $('.user_login_input input').blur(function(){
// 	if($.trim($(this).val())==""){
// 		$(this).siblings('b').show();
// 	}else{
// 		if($(this).attr('name')=="tel" && isPhoneNo($.trim($('input[name="tel"]').val())) == false){
// 			$(this).siblings('b').show();
// 		}else{
// 			$(this).siblings('b').hide();
// 		}
// 	} 
// })