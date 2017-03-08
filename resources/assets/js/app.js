require('./bootstrap') // bootstrap load our application wide dependencies
/**
 * app.js
 * ------
 * This is where global application code should be,
 * app.js is the 'entry point' for our webpack configuration
 *
 *
 */

$(document).ready(function(){
 $('.register.button').click(_ => {
   $('.ui.modal.register').modal('show')
 })
})

/*
  $(document).ready(function(){
   $('.register').click(function(){
     this.preventDefault();
     $('.coupled.modal').modal({ allowMultiple: false });
     $('.second.modal').modal('attach events', ".first.modal .button");
     $('.first.modal').modal('show');
   });
   $('.login').click(function(){
     //e.preventDefault();
     $('.ui.basic.modal').modal('show');
   });
 }); */


let headerElement = $('#header')[0]
if (window.location.hash) {
  header.classList.add('headroom--unpinned')
}
let headroom = new Headroom(headerElement)
headroom.init()


$(function () {
  $('.add-record.button').click(_ => {
    $('.add-record.modal').modal({
      onApprove: _ => {
        $('form#add-record').submit()
      }
    }).modal('show')
  })

  $('.add-bill-org.button').click(_ => {
    $('.ui.modal.record-issuer').modal({
      onApprove: function () {
        $('form#add-record-issuer').submit()
      }
    }).modal('show')
  })

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
