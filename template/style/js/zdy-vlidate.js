/**
 * Created by zhaoyige on 2017/7/15.
 */
// 手机号码验证
jQuery.validator.addMethod("mobile", function(value, element) {
  var length = value.length;
  var mobile =  /^(((13[0-9]{1})|(15[0-9]{1}))+\d{8})$/
  return this.optional(element) || (length == 11 && mobile.test(value));
}, "*请输入正确格式手机号码");

// 电话号码验证
jQuery.validator.addMethod("phone", function(value, element) {
  var tel = /^(0[0-9]{2,3}\-)?([2-9][0-9]{6,7})+(\-[0-9]{1,4})?$/;
  return this.optional(element) || (tel.test(value));
}, "电话号码格式错误");

// 邮政编码验证
jQuery.validator.addMethod("zipCode", function(value, element) {
  var tel = /^[0-9]{6}$/;
  return this.optional(element) || (tel.test(value));
}, "邮政编码格式错误");

// QQ号码验证
jQuery.validator.addMethod("qq", function(value, element) {
  var tel = /^[1-9]\d{4,9}$/;
  return this.optional(element) || (tel.test(value));
}, "qq号码格式错误");

// 字母和数字的验证
jQuery.validator.addMethod("chrnum", function(value, element) {
  var chrnum = /^([a-zA-Z0-9]+)$/;
  return this.optional(element) || (chrnum.test(value));
}, "*只能输入数字和字母(字符A-Z, a-z, 0-9)");

// 中文的验证
jQuery.validator.addMethod("chinese", function(value, element) {
  var chinese = /^[\u4e00-\u9fa5]+$/;
  return this.optional(element) || (chinese.test(value));
}, "*请输入中文");

// 下拉框验证
$.validator.addMethod("selectNone", function(value, element) {
  return value != "请选择";
}, "*必须选择一项");

// 字节长度验证
jQuery.validator.addMethod("byteRangeLength", function(value, element, param) {
  var length = value.length;
  for (var i = 0; i < value.length; i++) {
    if (value.charCodeAt(i) > 127) {
      length++;
    }
  }
  return this.optional(element) || (length >= param[0] && length <= param[1]);
}, $.validator.format("请确保输入的值在{0}-{1}个字节之间(一个中文字算2个字节)"));
