require('./bootstrap') // bootstrap load our application wide dependencies
/**
 * app.js
 * ------
 * This is where global application code should be,
 * app.js is the 'entry point' for our webpack configuration
 *
 *
 */
 // library
// init headroom module

// /dashboard modal controls
$(function () {
  $('.add-record.button').click(_ => {
    $('.add-record.modal').modal({
      onApprove: function () {
          $('.ui.form').submit();
          return false;
      },
      onSuccess: function () {
          $('form#add-record').submit();
          $('.modal').modal('hide');
      }
    }).modal('show')
  })

  $('.add-bill-org.button').click(_ => {
    $('.ui.modal.record-issuer').modal({
      onApprove: function () {
          $('.ui.form').submit();
          // need to return false to not close modal
          // in case input failed the validation test
          return false;
      },
      onSuccess: function () {
          $('form#add-record-issuer').submit();
          $('.modal').modal('hide');
      }
    }).modal('show')
  })
  
  $('.ui.form.record-issuer')
        .form({
        fields: {
            name: {
                identifier: 'name',
                rules: [
                    {
                        type   : 'empty',
                        prompt : 'Please enter record issuer name'
                    }
                ]
            },
        }
    });
    
$('.ui.form.add-record')
    .form({
        fields: {
            record: {
                identifier: 'record',
                rules: [
                    {
                        type   : 'empty',
                        prompt : 'Please choose a file to upload'
                    }
                ]
            },
            issue_date: {
                identifier: 'issue_date',
                rules: [
                    {
                        type   : 'empty',
                        prompt : 'Please enter the date of issue'
                    }
                ]
            },
            period: {
                identifier: 'period',
                rules: [
                    {
                        type   : 'empty',
                        prompt : 'Please enter the record period'
                    }
                ]
            },
            due_date: {
                identifier: 'due_date',
                rules: [
                    {
                        type   : 'empty',
                        prompt : 'Please enter the due date'
                    }
                ]
            },
            amount: {
                identifier: 'amount',
                rules: [
                    {
                        type   : 'empty',
                        prompt : 'Please enter the amount'
                    }
                ]
            },
        }
    });

  $('.delete-record.button').click((e) => {
    e.preventDefault()
    $('form#delete-record').submit()
  })

  $('.logout.button').click((e) => {
    e.preventDefault()
    axios.post('/logout').then(_ => {
      location.reload()
    })
  })
})

// calendar settings
$('.ui.calendar').calendar({
  type: 'date',
  formatter: {
    date: function (date, settings) {
      if (!date) return ''
      var day = date.getDate()
      var month = date.getMonth() + 1
      var year = date.getFullYear()
      return day + '/' + month + '/' + year
    }
  }
})

$('.ui.calendar-month').calendar({
  type: 'month',
  formatter: {
    date: function (date, settings) {
      if (!date) return ''
      var month = settings.text.monthsShort[date.getMonth()]
      var year = date.getFullYear()
      return month + '/' + year
    }
  }
})
