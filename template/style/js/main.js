/**
 * Created by zhaoyige on 2017/7/16.
 */
$().ready(function(){
  var department = ''//数据库取值
  for(var i = 0 ; i < department.length; i++) {
    var flag = "<option value='"+i+"'>" +department[i]+"</option>"
    $('#department').append(flag)
  }
  var company = ['柳州市总工会','柳州质检','柳州人设','..'] //数据库取值生成数组
  for(var i = 0 ; i < company.length; i++) {
    var flag = "<option value='"+i+"'>" +company[i]+"</option>"
    $('#company').append(flag)
  }
  function validform(obj){
    return $(obj).validate({
      rules:{
        user: {
          required: true,
          mobile: true
        },
        password: {
          required: true,
          chrnum: true,
          minlength: 6
        },
        userName: {
          required: true,
          chinese: true
        },
        department: {
          required: true,
          selectNone: true
        },
        schoolName: {
          required: true,
          chinese: true
        },
        //巡检运维表单验证
        unitName: {
          required: true,
          selectNone: true
        },
        category: {
          required: true,
          selectNone: true
        },
        InspectionDate: {
          required: true
        },
        InspectionContent: {
          required: true
        },
        problemLog: {
          required: true
        },
        treatmentMethods:{
          required: true
        },
        //维修运维表单验证
        address: {
          required: true
        },
        repairDate: {
          required: true
        },
        handleDate: {
          required: true
        },
        repairContent: {
          required: true
        },
        failureReport: {
          required: true
        },
        resolvent: {
          required: true
        },
        //新媒体运维表单验证
        release: {
          required: true
        },
        title: {
          required: true
        },
        //教育运维表单验证
        icloudCategory: {
          required: true,
          selectNone: true
        },
        outTime: {
          required: true
        },
        icoudDescribe:{
          required: true
        }
      },
      errorElement: "p",
      messages:{
        password: {
          minlength: "*密码长度不能低于6个字符"
        }
      }

    })
  }

  $(validform())
  /*注册页面验证*/
  $('.js-register').on('click',function(e){
    e.preventDefault()
    if(validform('#register').form()){ //通过验证之后执行
    //  alert('注册成功')
    //  window.location.href="login.html"
    }else{

    }
  })

  /*巡检页面验证*/
  $('.js-Inspection').on('click',function(){
    if(validform('#Inspection').form()){ //通过验证之后执行
    //  alert('提交成功')
    //  window.location.href="admin.html"
    }else{

    }
  })
  /*维修页面验证*/
  $('.js-service').on('click',function(){
    if(validform('#service').form()){ //通过验证之后执行
    //  alert('提交成功')
    //  window.location.href="admin.html"
    }else{

    }
  })
  /*新媒体验证*/
  $('.js-newMedia').on('click',function(){
    if(validform('#newMedia').form()){ //通过验证之后执行
    //  alert('提交成功')
    //  window.location.href="admin.html"
    }else{

    }
  })
  /*教育页面验证*/
  $('.js-icloud').on('click',function(){
    if(validform('#icloud').form()){ //通过验证之后执行
    //  alert('提交成功')
    //  window.location.href="admin.html"
    }else{

    }
  })
  /*时间插件*/
    /*新媒体运维*/
  $('#releaseDate').datePicker({
    beginyear: 2017,
    theme: 'datetime'
  });
    /*教育*/
  $('#outTime').datePicker({
    beginyear: 2017,
    theme: 'datetime'
  });
  /*巡检*/
  $('#InspectionDate').datePicker({
    beginyear: 2017,
    theme: 'datetime'
  });
  /*报修*/
  $('#repairDate').datePicker({
    beginyear: 2017,
    theme: 'datetime'
  });
  $('#handleDate').datePicker({
    beginyear: 2017,
    theme: 'datetime'
  });
   /*合同录入*/
  $('#contractStar').datePicker({
    beginyear: 2017,
    theme: 'date'
  });
  $('#contractEnd').datePicker({
    beginyear: 2017,
    theme: 'date'
  });

})