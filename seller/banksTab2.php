
<form id="banksAdd" enctype="multipart/form-data">
<div class="row">
	<div class="form-group col-lg-3 ">
		<label for="site">Bank name</label>
		<input type="text" name="site" id="site" class="form-control input-sm" placeholder="Bank of America" required="">
	</div>
		<div class="form-group col-lg-3 ">
		<label for="balance">Balance</label>
		<input type="text" name="balance" id="balance" class="form-control input-sm" placeholder="5000$" required="">
	</div>
</div>

<div class="row">
  <div class="form-group col-lg-4">
    <label>Proof Image (optional, jpg/png/gif/webp, &lt;5MB)</label>
    <input type="file" name="proof" accept="image/*" class="form-control input-sm">
  </div>
</div>
<div class="row">
	<div class="form-group col-lg-3 ">
		<label for="infos">Available Information</label>
		<input type="text" name="infos" id="infos" class="form-control input-sm" placeholder="SSN/DOB/BILLING/IP/User Agent..." required="">
	</div>
	<div class="form-group col-lg-3 ">
		<label for="country">Country</label>
<select name="country" id="banks_country" class="form-control input-sm" required="">
 <option value="">-- Loading countries... --</option>
</select>
		</div>
</div>

<label for="text">ALL ACCOUNT INFO HERE</label>
<textarea name="inputs" class="form-control " rows="3" placeholder="User : XXXXXXX | Pass : XXXXXXX" required></textarea>
<div class="col-md-3">
				<label for="price">Price</label>
	<tr>
		
		<td><input placeholder="5" type="text" name="price" class="form-control input-sm" required=""></td><br>
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
  $("#banksAdd").off("submit").on("submit", function(e) {
    e.preventDefault();
    $("#banksAdd button[type=submit]").prop("disabled", true);
    $.ajax({
      type: "POST",
      url: "banksAdd.html",
      data: new FormData(document.getElementById("banksAdd")),
      processData: false,
      contentType: false,
      success: function(data) {
        $("#banksAdd button[type=submit]").prop("disabled", false);
        var msg = $.trim(data);
        if (msg === "") msg = '<span class="text-muted">No response from server.</span>';
        $("#result").html(msg).show();
        if (msg.indexOf("Added") !== -1) {
        $('#site').val('');
        $('#balance').val('');
        $('#infos').val('');
        }
      },
      error: function(xhr) {
        $("#banksAdd button[type=submit]").prop("disabled", false);
        $("#result").html('<span class="text-danger">Error ' + xhr.status + ': ' + xhr.statusText + '</span>').show();
      }
    });
  });
})();
</script>
<script type="text/javascript">
(function(){
  if (window.__banksCountryInit) return; window.__banksCountryInit = true;
  var $c = $('#banks_country');
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
