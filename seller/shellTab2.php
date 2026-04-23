							 <form id="shellAdd" enctype="multipart/form-data">
<div class="row">
	<div class="form-group col-lg-3 ">
		<label for="shell_host">Shell URL</label>
		<input type="text" name="shell_host" id="shell_host" class="form-control input-sm" placeholder="http://domain.com/path/file.php" required="">
	</div>
</div>

<div class="row">
	<div class="form-group col-lg-4">
		<label for="shell_country">Country <span style="color:red">*</span></label>
		<select name="country" id="shell_country" class="form-control input-sm" required="">
			<option value="__auto__" selected>-- Auto-detect from IP --</option>
		</select>
	</div>
</div>
<div class="row">
  <div class="form-group col-lg-4">
    <label>Proof Image (optional, jpg/png/gif/webp, &lt;5MB)</label>
    <input type="file" name="proof" accept="image/*" class="form-control input-sm">
  </div>
</div>
<div class="row">

<div class="col-md-3">
<table class="table ">
	<tbody><tr>
		<th>Price</th>
	</tr>
	<tr>
		<td><input placeholder="5" type="text" name="price" class="form-control input-sm" required=""></td>
	</tr>
</tbody></table>
</div>
<div class="form-group col-lg-10">
	<button type="submit" name="submit" class="btn btn-primary btn-md">Add  <span class="glyphicon glyphicon-indent-left"></span></button>
	<input type="hidden" name="start" value="work" />
</div>
</div>
</form>
<div class="well well-sm col-md-6"><b>[Respone]</b><div id="result"></div></div>
<script type="text/javascript">
(function(){
  $("#shellAdd").off("submit").on("submit", function(e) {
    e.preventDefault();
    $("#shellAdd button[type=submit]").prop("disabled", true);
    $.ajax({
      type: "POST",
      url: "shellAdd.html",
      data: new FormData(document.getElementById("shellAdd")),
      processData: false,
      contentType: false,
      success: function(data) {
        $("#shellAdd button[type=submit]").prop("disabled", false);
        var msg = $.trim(data);
        if (msg === "") msg = '<span class="text-muted">No response from server.</span>';
        $("#result").html(msg).show();
        if (msg.indexOf("Added") !== -1) {
        $('#shell_host').val('');
        }
      },
      error: function(xhr) {
        $("#shellAdd button[type=submit]").prop("disabled", false);
        $("#result").html('<span class="text-danger">Error ' + xhr.status + ': ' + xhr.statusText + '</span>').show();
      }
    });
  });
})();
</script>
<script type="text/javascript">
(function(){
  if (window.__shellCountryInit) return; window.__shellCountryInit = true;
  var $c = $('#shell_country');
  if (!$c.length) return;
  var dataUrl = 'https://cdn.jsdelivr.net/gh/dr5hn/countries-states-cities-database@master/json/countries.json';
  var preserved = $c.val();
  $c.html('<option value="">-- Loading countries... --</option>');
  $.ajax({url: dataUrl, dataType:'json', cache:true})
    .done(function(list){
      list = (list || []).filter(function(x){ return x && x.name; });
      list.sort(function(a,b){return String(a.name).localeCompare(String(b.name));});
      var html = '<option value="__auto__" selected>-- Auto-detect from IP --</option><option value="">-- Select country (manual) --</option>';
      for (var i=0;i<list.length;i++){
        var n = list[i].name;
        var safe = $('<div>').text(n).html();
        html += '<option value="'+ safe +'">'+ safe +'</option>';
      }
      $c.html(html);
      if (preserved) $c.val(preserved);
    })
    .fail(function(){
      $c.html('<option value="">-- Failed to load; refresh page --</option>');
    });
})();
</script>
