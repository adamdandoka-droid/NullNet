<form id="cpanelAdd" enctype="multipart/form-data">
<div class="row">
	<div class="form-group col-lg-3 ">
		<label for="cpanel_host">Host/IP</label>
		<input type="text" name="cpanel_host" id="cpanel_host" class="form-control input-sm" placeholder="https://domain.com:2083" required="">
	</div>
</div>
<div class="row">
	<div class="form-group col-lg-3 ">
		<label for="cpanel_login">Login</label>
		<input type="text" name="cpanel_login" id="cpanel_login" class="form-control input-sm" placeholder="user" required="">
	</div>
	<div class="form-group col-lg-3 ">
		<label for="cpanel_pass">Password</label>
		<input type="text" name="cpanel_pass" id="cpanel_pass" class="form-control input-sm" placeholder="abc123" required="">
	</div>
</div>

<div class="row">
	<div class="form-group col-lg-4">
		<label for="cpanel_country">Country <span style="color:red">*</span></label>
		<select name="country" id="cpanel_country" class="form-control input-sm" required="">
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

<div class="col-md-5">
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

<div class="well well-sm col-md-6"><b>[Response]</b><div id="result"></div></div>
<script type="text/javascript">
(function(){
  $("#cpanelAdd").off("submit").on("submit", function(e) {
    e.preventDefault();
    $("#cpanelAdd button[type=submit]").prop("disabled", true);
    $.ajax({
      type: "POST",
      url: "cpanelAdd.html",
      data: new FormData(document.getElementById("cpanelAdd")),
      processData: false,
      contentType: false,
      success: function(data) {
        $("#cpanelAdd button[type=submit]").prop("disabled", false);
        var msg = $.trim(data);
        if (msg === "") msg = '<span class="text-muted">No response from server.</span>';
        $("#result").html(msg).show();
        if (msg.indexOf("Added") !== -1) {
        $('#cpanel_host').val('');
        $('#cpanel_login').val('');
        $('#cpanel_pass').val('');
        }
      },
      error: function(xhr) {
        $("#cpanelAdd button[type=submit]").prop("disabled", false);
        $("#result").html('<span class="text-danger">Error ' + xhr.status + ': ' + xhr.statusText + '</span>').show();
      }
    });
  });
})();
</script>
<script type="text/javascript">
(function(){
  if (window.__cpanelCountryInit) return; window.__cpanelCountryInit = true;
  var $c = $('#cpanel_country');
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
