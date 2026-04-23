
<form id="rdpAdd" enctype="multipart/form-data">
<div class="row">
        <div class="form-group col-lg-3 ">
                <label for="rdp_host">Host/IP</label>
                <input type="text" name="rdp_host" id="rdp_host" class="form-control input-sm" placeholder="1.1.1.1" required="">
        </div>
</div>
<div class="row">
        <div class="form-group col-lg-3 ">
                <label for="rdp_login">Login</label>
                <input type="text" name="rdp_login" id="rdp_login" class="form-control input-sm" placeholder="admin" required="">
        </div>
        <div class="form-group col-lg-3 ">
                <label for="rdp_pass">Password</label>
                <input type="text" name="rdp_pass" id="rdp_pass" class="form-control input-sm" placeholder="abc123" required="">
        </div>
</div>
<div class="row">
        <div class="form-group col-lg-4">
                <label for="rdp_country">Country <span style="color:red">*</span></label>
                <select name="rdp_country" id="rdp_country" class="form-control input-sm" required="">
                <option value="">-- Loading countries... --</option>
                </select>
        </div>
        <div class="form-group col-lg-4">
                <label for="rdp_hosting_type">Hosting Type <span style="color:red">*</span></label>
                <select class="form-control input-sm hosting-type-select" name="rdp_hosting_type" id="rdp_hosting_type" required="">
                        <option value="">-- Select --</option>
                        <option value="Hacked" data-color="#e74c3c">&#x1F534; Hacked</option>
                        <option value="Created" data-color="#3498db">&#x1F535; Created</option>
                </select>
        </div>
        <div class="form-group col-lg-4">
                <label for="rdp_created_at">Created At <span style="color:red">*</span></label>
                <input type="datetime-local" name="rdp_created_at" id="rdp_created_at" class="form-control input-sm" required="">
        </div>
</div>
<div class="row">
        <div class="form-group col-lg-6">
                <label for="rdp_hosting">Hosting / ISP <small class="text-muted">(leave blank to auto-detect from IP)</small></label>
                <input type="text" name="rdp_hosting" id="rdp_hosting" class="form-control input-sm" placeholder="e.g. OVH, AWS, Comcast Cable, etc.">
        </div>
</div>

<div class="row">
  <div class="form-group col-lg-4">
    <label>Proof Image (optional, jpg/png/gif/webp, &lt;5MB)</label>
    <input type="file" name="proof" accept="image/*" class="form-control input-sm">
  </div>
</div>
<div class="row">

<div class="col-md-6">
<table class="table ">
        <tbody><tr>
                <th>Access</th>
                <th>Windows</th>
                <th>RAM</th>
                <th>Price</th>
        </tr>
        <tr>
                <td>
                        <select class="form-control input-sm" name="access" required="">
                                <option selected="">USER</option>
                                <option>ADMIN</option>
                        </select>
                </td>
                <td>
                <select class="form-control input-sm" name="windows" required="">
                                <option>ME</option>
                                <option>2000</option>
                                <option>XP</option>
                                <option>2003</option>
                                <option>Vista</option>
                                <option>7</option>
                                <option>8</option>
                                <option>10</option>
                                <option>2008</option>
                                <option selected="">2012</option>
                                <option>2016</option>

                </select>
                </td>
                <td><input placeholder="512MB/1GB/2GB" type="text" name="ram" class="form-control input-sm" required=""></td>
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
        <div class="well well-sm col-md-6" ><b>[Response]</b><div id="result"></div></div>
</div>
<script type="text/javascript">
$("#rdpAdd").off('submit').on('submit', function(e) {
  e.preventDefault();
  $('#rdpAdd button[type=submit]').prop('disabled', true);
  $.ajax({
    type: "POST",
    url: 'rdpAdd.php',
    data: new FormData(document.getElementById("rdpAdd")),
    processData: false,
    contentType: false,
    success: function(data){
      $('#rdpAdd button[type=submit]').prop('disabled', false);
      $("#result").html(data).show();
      if (typeof data === 'string' && data.indexOf('Successfully Added') !== -1){
        $('#rdp_host').val('');
        $('#rdp_login').val('');
        $('#rdp_pass').val('');
      }
    },
    error: function(){
      $('#rdpAdd button[type=submit]').prop('disabled', false);
      $("#result").html('Network error, please try again.').show();
    }
  });
  return false;
});
</script>
<script type="text/javascript">
(function(){
  if (window.__rdpCountryInit) return; window.__rdpCountryInit = true;
  var $c = $('#rdp_country');
  var dataUrl = 'https://cdn.jsdelivr.net/gh/dr5hn/countries-states-cities-database@master/json/countries.json';

  $.ajax({url: dataUrl, dataType:'json', cache:true})
    .done(function(list){
      list = (list || []).filter(function(x){ return x && x.name; });
      list.sort(function(a,b){return String(a.name).localeCompare(String(b.name));});
      var html = '<option value="">-- Select country --</option>';
      for (var i=0;i<list.length;i++){
        var n = list[i].name;
        html += '<option value="'+ $('<div>').text(n).html() +'">'+ $('<div>').text(n).html() +'</option>';
      }
      $c.html(html);
    })
    .fail(function(){
      $c.html('<option value="">-- Failed to load; refresh page --</option>');
    });
})();
</script>
