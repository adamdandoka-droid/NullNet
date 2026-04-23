<form id="leadsAdd" enctype="multipart/form-data">
<div class="row">
	<div class="form-group col-lg-3 ">
		<label for="lead_link">Download Link</label>
		<input type="text" name="link" class="form-control input-sm" placeholder="https://anonfiles.com/file/sdfa9eebab0c1247828f06ddb98280" required="">
	</div>
    <div class="form-group col-lg-3 ">
    <label for="lead_number">Email Number (k)</label>
    <input type="text" name="emailsk" class="form-control input-sm" placeholder="10k" required="">
  </div>
</div>
<div class="row">
  <div class="form-group col-lg-3 ">
    <label for="lead_about">Description</label>
    <input type="text" name="infos" class="form-control input-sm" placeholder="HOTMAIL / From Shopping" required="">
  </div>
    <div class="form-group col-lg-3 ">
    <label for="lead_country">Country</label>
<select name="country" id="lead_country" class="form-control input-sm" required>
 <option value="">-- Loading countries... --</option>
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
<div class="row">
	<div class="well well-sm col-md-6"><b>[Response]</b><div id="result"></div></div>
</div>
<script type="text/javascript">
(function(){
  $("#leadsAdd").off("submit").on("submit", function(e) {
    e.preventDefault();
    $("#leadsAdd button[type=submit]").prop("disabled", true);
    $.ajax({
      type: "POST",
      url: "leadAdd.html",
      data: new FormData(document.getElementById("leadsAdd")),
      processData: false,
      contentType: false,
      success: function(data) {
        $("#leadsAdd button[type=submit]").prop("disabled", false);
        var msg = $.trim(data);
        if (msg === "") msg = '<span class="text-muted">No response from server.</span>';
        $("#result").html(msg).show();
        if (msg.indexOf("Added") !== -1) {
        $('#link').val('');
        $('#emailsk').val('');
        $('#infos').val('');
        }
      },
      error: function(xhr) {
        $("#leadsAdd button[type=submit]").prop("disabled", false);
        $("#result").html('<span class="text-danger">Error ' + xhr.status + ': ' + xhr.statusText + '</span>').show();
      }
    });
  });
})();
</script>
<script type="text/javascript">
(function(){
  if (window.__leadCountryInit) return; window.__leadCountryInit = true;
  var $c = $('#lead_country');
  if (!$c.length) return;
  var dataUrl = 'https://cdn.jsdelivr.net/gh/dr5hn/countries-states-cities-database@master/json/countries.json';
  var preserved = $c.val();
  $c.html('<option value="">-- Loading countries... --</option>');
  $.ajax({url: dataUrl, dataType:'json', cache:true})
    .done(function(list){
      list = (list || []).filter(function(x){ return x && x.name; });
      list.sort(function(a,b){return String(a.name).localeCompare(String(b.name));});
      var html = '<option value="">-- Select country --</option>';
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
