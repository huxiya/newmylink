(function resize () {
  var scale = 1 / devicePixelRatio
  document.querySelector('meta[name="viewport"]').setAttribute('content' , 'initial-scale=' + scale + ' , maximum-scale=' + scale + ' , user-scalable=no')
  var reSize = function () {
    var deviceWidth = document.documentElement.clientWidth > 1300 ? 1300 : document.documentElement.clientWidth
    document.documentElement.style.fontSize = (deviceWidth / 6.4) + 'px'
  }
  reSize()
  window.onresize = reSize
})()