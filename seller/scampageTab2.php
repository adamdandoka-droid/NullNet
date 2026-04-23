<form id="scampageAdd" enctype="multipart/form-data">
<div class="row">
	<div class="form-group col-lg-3 ">
		<label >Download link</label>
		<input type="text" name="link" class="form-control input-sm" placeholder="https://anonfiles.com/file/sdfa9eebab0c1247828f06ddb98280" required="">
	</div>
    <div class="form-group col-lg-3 ">
    <label >Scampage name</label>
    <input type="text" name="scamname" class="form-control input-sm" placeholder="Paypal scampage" required="">
  </div>
</div>
<div class="row">
  <div class="form-group col-lg-3 ">
    <label >Description</label>
    <input type="text" name="infos" class="form-control input-sm" placeholder="Undetected ,new style etc.." required="">
  </div>
    <div class="form-group col-lg-3 ">
    <label >Price</label>
		<td><input placeholder="5" type="text" name="price" class="form-control input-sm" required=""></td>

  </div>
</div>


<div class="row">
  <div class="form-group col-lg-4">
    <label>Proof Image (optional, jpg/png/gif/webp, &lt;5MB)</label>
    <input type="file" name="proof" accept="image/*" class="form-control input-sm">
  </div>
</div>
<div class="row">

<div class="form-group col-lg-10">
	<button type="submit" name="submit" class="btn btn-primary btn-md">Add  <span class="glyphicon glyphicon-indent-left"></span></button>
<input type="hidden" name="start" value="work" />
	</div>
</div>
</form>

<div class="row">
	<div class="well well-sm col-md-6"><b>[Response]</b><div id="result"></div></div>
</div>
<script type="text/javascript">
(function(){
  $("#scampageAdd").off("submit").on("submit", function(e) {
    e.preventDefault();
    $("#scampageAdd button[type=submit]").prop("disabled", true);
    $.ajax({
      type: "POST",
      url: "scampageAdd.html",
      data: new FormData(document.getElementById("scampageAdd")),
      processData: false,
      contentType: false,
      success: function(data) {
        $("#scampageAdd button[type=submit]").prop("disabled", false);
        var msg = $.trim(data);
        if (msg === "") msg = '<span class="text-muted">No response from server.</span>';
        $("#result").html(msg).show();
        if (msg.indexOf("Added") !== -1) {
        $('#link').val('');
        $('#scamname').val('');
        $('#infos').val('');
        }
      },
      error: function(xhr) {
        $("#scampageAdd button[type=submit]").prop("disabled", false);
        $("#result").html('<span class="text-danger">Error ' + xhr.status + ': ' + xhr.statusText + '</span>').show();
      }
    });
  });
})();
</script>