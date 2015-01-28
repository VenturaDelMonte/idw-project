
window.onload = function(ev)
{
	loadIndices();
}

function loadIndices()
{
	var req = {"id": "loadIndices"};
	$.ajax({
		type: "POST",
		url: 'engine.php',
		data: JSON.stringify(req),
		contentType: "application/json; charset=utf-8",
		dataType: "json",
		success: function(data) {
			$.each(data, function(index) {
		    	// console.log(data[index]);
		    	var a = $('<a>').attr('href', data[index]._id.$id).append($('<span>').append(data[index].name));
				a.click(function(e) {
					e.preventDefault();
					loadIndex(data[index]._id.$id);
				});
				$('#indices').append($('<li>').append(a))
			});
		},
		failure: function(errMsg) {
			alert(errMsg);
		}
	});
}

function loadIndex(idx)
{
	$('#select-index-h1').addClass('hidden');
	$('#assets-table').removeClass('hidden');
	$('#assets-table').empty();
	var req = {"id": "loadAssets", "data": idx};
	$.ajax({
		type: "POST",
		url: 'engine.php',
		data: JSON.stringify(req),
		contentType: "application/json; charset=utf-8",
		dataType: "json",
		success: function(data) {
			var tr = $('<tr>');
			var cnt = -2;
			$.each(data, function(index) {
		    	// console.log(data[index]);
		    	tr.append($('<td>').append($('<span>').append(data[index])));
		    	if (cnt % 3 == 0)
		    	{
		    		$('#assets-table').append(tr);
		    		tr = $('<tr>');

		    	}
		    	cnt++;
		    	
			});
		},
		failure: function(errMsg) {
			alert(errMsg);
		}
	});

}