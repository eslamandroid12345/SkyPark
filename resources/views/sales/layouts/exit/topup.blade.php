<form class="py-3" id="topUpSubmit" action="{{route('exit.update',$id)}}" method="POST">
    @method('PUT')
    @csrf
    <div>
        <label>TopUp Time (h) </label>
        <input class="form-control" type="number" required name="top_up_hours" min="1" max="24" oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*?)\..*/g, '$1');"/>
    </div>
    <button type="submit" form="topUpSubmit" class="btn bg-gradient-primary m-3 mb-0"> Confirm </button>
</form>
<script>
    $('form#topUpSubmit').submit(function (e) {
        e.preventDefault();
        var formData = new FormData(this);
        var url = $('#topUpSubmit').attr('action');
        $.ajax({
            url:url,
            type: 'POST',
            data: formData,
            beforeSend: function(){
                $('#topUpSubmit').hide()
                $('#topUpBody').append(loader)
            },
            complete: function(){
                setTimeout(function () {

                    $('#topUpBody > .linear-background').hide(loader)
                    $('#topUpSubmit').show()
                },200)
            },
            success: function (data) {
                if (data.status == 200){
                    setTimeout(function () {
                        $('#topUp').modal('hide')
                        window.open(data.url, '_blank').focus()
                        location.reload()
                    },300)
                }else {
                    toastr.error('error');
                }

            },
            error: function (data) {
                if (data.status === 500) {
                    toastr.error('error');
                }
                else if (data.status === 422) {
                    var errors = $.parseJSON(data.responseText);
                    $.each(errors, function (key, value) {
                        if ($.isPlainObject(value)) {
                            $.each(value, function (key, value) {
                                toastr.error(value,key);
                            });

                        } else {
                        }
                    });
                }else {

                    toastr.error('there in an error');
                }
            },//end error method

            cache: false,
            contentType: false,
            processData: false
        });
    })

</script>
