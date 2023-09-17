<div class="modal-header">
    <h5 class="modal-title" id="example-Modal3">Edit Visitor</h5>
    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
<div class="modal-body">
    <form id="updateForm" method="POST" enctype="multipart/form-data" action="{{route('visitors.update',$visitor->id)}}" >
    @csrf
        @method('PUT')
        <input type="hidden" name="id" value="{{$visitor->id}}">
        <div class="form-group">
            <label for="photo" class="form-control-label">Photo</label>
            <input type="file" class="dropify" name="photo"
                   accept="image/png, image/gif, image/jpeg,image/jpg"
                   data-default-file="{{get_user_photo($visitor->photo)}}"/>
            <span class="form-text text-danger text-center">accept only png, gif, jpeg, jpg</span>
        </div>

        <div class="row">
            <div class="col-6">
                <div class="form-group">
                    <label for="title" class="form-control-label">Title</label>
                    <input type="text" required class="form-control" name="title" value="{{$visitor->title}}">
                </div>
            </div>
            <div class="col-6">
                <div class="form-group">
                    <label for="1_hours" class="form-control-label">1H price</label>
                    <input type="number" required class="form-control numbersOnly" name="1_hours" value="{{$visitor['1_hours']}}">
                </div>
            </div>
            <div class="col-6">
                <div class="form-group">
                    <label for="2_hours" class="form-control-label">2H price</label>
                    <input type="number" required class="form-control numbersOnly" name="2_hours" value="{{$visitor['2_hours']}}">
                </div>
            </div>
            <div class="col-6">
                <div class="form-group">
                    <label for="3_hours" class="form-control-label">3H price</label>
                    <input type="number" required class="form-control numbersOnly" name="3_hours" value="{{$visitor['3_hours']}}">
                </div>
            </div>

            <div class="col-6">
                <div class="form-group">
                    <label for="4_hours" class="form-control-label">4H price</label>
                    <input type="number" required class="form-control numbersOnly" name="4_hours" value="{{$visitor['4_hours']}}">
                </div>
            </div>

            <div class="col-6">
                <div class="form-group">
                    <label for="5_hours" class="form-control-label">5H price</label>
                    <input type="number" required class="form-control numbersOnly" name="5_hours" value="{{$visitor['5_hours']}}">
                </div>
            </div>

            <div class="col-12">
                <p class="fw-bolder">
                    TopUp Prices
                </p>
                <br>
            </div>

            <div class="col-6">
                <div class="form-group">
                    <label for="top_1_hours" class="form-control-label">1H price</label>
                    <input type="number"  class="form-control numbersOnly" name="top_1_hours" value="{{($visitor->top_up['1_hours']) ?? ''}}">
                </div>
            </div>
            <div class="col-6">
                <div class="form-group">
                    <label for="top_2_hours" class="form-control-label">2H price</label>
                    <input type="number"  class="form-control numbersOnly" name="top_2_hours" value="{{($visitor->top_up['2_hours']) ?? ''}}">
                </div>
            </div>
            <div class="col-6">
                <div class="form-group">
                    <label for="top_3_hours" class="form-control-label">3H price</label>
                    <input type="number"  class="form-control numbersOnly" name="top_3_hours" value="{{($visitor->top_up['3_hours']) ?? ''}}">
                </div>
            </div>
            <div class="col-6">
                <div class="form-group">
                    <label for="top_4_hours" class="form-control-label">4H price</label>
                    <input type="number"  class="form-control numbersOnly" name="top_4_hours" value="{{($visitor->top_up['4_hours']) ?? ''}}">
                </div>
            </div>
            <div class="col-6">
                <div class="form-group">
                    <label for="top_5_hours" class="form-control-label">5H price</label>
                    <input type="number"  class="form-control numbersOnly" name="top_5_hours" value="{{($visitor->top_up['5_hours']) ?? ''}}">
                </div>
            </div>
        </div>


        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-success" id="updateButton">Update</button>
        </div>
    </form>
</div>
<script>
    $('.dropify').dropify()
</script>
