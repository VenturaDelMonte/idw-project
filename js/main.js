
window.onload = function(ev)
{
	loadIndices();
}

function cleanAssetInfo()
{
	$('#asset-info').addClass('hidden');
	$('#wiki-data').empty();
	$('#yahoo-table').empty();
	$('#wikipedia-panel').addClass('hidden');
	$('#trends-panel').addClass('hidden');
	$('#yahoo-panel').addClass('hidden');
	$('#news-panel').addClass('hidden');
	$("#news-timeline").empty();
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
					cleanAssetInfo();	
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
	//$('#select-index-h1').addClass('hidden');
	$('#select-index-h1').text("");
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
			var cnt = -3;
			$.each(data, function(index) {
		    	// console.log(data[index]);
		    	var a = $('<a>').attr('href', data[index].id.$id).append(data[index].name);
		    	a.click(function (e){
		    		e.preventDefault();
		    		loadAsset(data[index].id.$id, data[index].name);
		    	});
		    	tr.append($('<td>').append(a));
		    	if (cnt % 4 == 0)
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

function loadAsset(id, name)
{
	$('#assets-table').empty();
	$('#yahoo-table').empty();
	$('#assets-table').addClass('hidden');	
	$('#asset-info').removeClass('hidden');	
	$('#select-index-h1').text(name);
	$('#wikipedia-panel').addClass('hidden');
	$('#trends-panel').addClass('hidden');
	$('#yahoo-panel').addClass('hidden');
	$('#news-panel').addClass('hidden');

	console.log(name);
	// wikipedia 
	
	var req = {"id": "loadWikipedia", "data": id};
	$.ajax({
		type: "POST",
		url: 'engine.php',
		data: JSON.stringify(req),
		contentType: "application/json; charset=utf-8",
		dataType: "json",
		success: function(data) {
			var content = data.data;
			//console.log(data.url);
			if (content.length > 0) {
				$('#wikipedia-panel').removeClass('hidden');
				$('#wiki-data').html(content);
				var a = $('<a>').attr('href', data.url).attr('target', "_blank");
				a.append("Continue reading on wikipedia");
				
				$('#wiki-data').append(a);
			}
			else
				$('#wiki-data').html('not found');
		},
		failure: function(errMsg) {
			alert(errMsg);
		}
	});

	// yahoo 

	var req = {"id": "loadYahooFinance", "data": id};
	$.ajax({
		type: "POST",
		url: 'engine.php',
		data: JSON.stringify(req),
		contentType: "application/json; charset=utf-8",
		dataType: "json",
		success: function(res) {
			if (!$.isArray(res.data)){
				$('#yahoo-panel').removeClass("hidden");
				var table = $('#yahoo-table');
				table.removeClass('hidden');
				$.each(res.data, function(key) {
					var tr = $('<tr>');
					var td1 = $('<td>');
					var td2 = $('<td>');
					//console.log(key);
					td1.append(key);
					td2.append(res.data[key]);
					tr.append(td1);
					tr.append(td2);
					table.append(tr);
				});
			 }
		},
		failure: function(errMsg) {
			alert(errMsg);
		}
	});


	// yahoo 

	var req = {"id": "loadGoogleNews", "data": id};
	$.ajax({
		type: "POST",
		url: 'engine.php',
		data: JSON.stringify(req),
		contentType: "application/json; charset=utf-8",
		dataType: "json",
		success: function(res) {
			if(res.length >0)
			{	
				$('#news-panel').removeClass("hidden");
				var timeline = $("#news-timeline");
				$.each(res, function(index){
					var li = $("<li>");
					if(index%2==1)
						li.addClass("timeline-inverted");
					//li.html("<div class=\"timeline-badge\"><i class=\"fa fa-check\"></i></div>");
					var panel = $("<div>").addClass("timeline-panel");
					
					var body = $("<div>").addClass("timeline-body").append(res[index]);
					
					panel.append(body);
					li.append(panel);
					timeline.append(li);
				});
			}
			//$('#google-news').html(res);
			
		},
		failure: function(errMsg) {
			alert(errMsg);
		}
	});

	//Google Trends

	var req = {"id": "loadTrends", "data": id};
	$.ajax({
		type: "POST",
		url: 'engine.php',
		data: JSON.stringify(req),
		contentType: "application/json; charset=utf-8",
		dataType: "json",
		success: function(res) {
			if(res.length>0)
			{
				$('#trends-panel').removeClass('hidden');
				$("#trends-data").empty();
				$("#trends-data").append($("<img>").attr("src", res));
			}
		},
		failure: function(errMsg) {
			alert(errMsg);
		}
	});
}