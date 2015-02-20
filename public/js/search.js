(function (window, $) {

  function debounce (fn, wait, immediate) {
    var timeout

    return function () {
      var ctx = this,
          args = arguments

      var later = function () {
        timeout = null
        
        if (!immediate) {
          fn.apply(ctx, args)
        }
      }

      var callNow = immediate && !timeout;

      clearTimeout(timeout)
      timeout = setTimeout(later, wait)

      if (callNow) {
        fn.apply(ctx, args)
      }
    }
  }

  // Make the contains selector case insensitive
  $.expr[':'].contains = $.expr[':'].Contains =  function (a, i, m) {
    return (a.textContent || a.innerText || "").toLowerCase().indexOf(m[3].toLowerCase()) >= 0;
  }

  $('#search-services').on('keydown', debounce(function () {
    var words = $(this).val().split(' ').filter(Boolean),
        rows  = $('.service-info')

    if (words.length) {
      rows.removeClass('hidden')
      $.each(words, function (i, word) {
        console.log(rows);
        rows.filter(':not(:contains("' + word + '"))').addClass('hidden')
      })
    } else {
      rows.removeClass('hidden')
    }
  }, 250))

})(window, $)
