function LadderChat(ladderID)
{
	lastUpdate = null;
	
	updateChat = function() {
		url = "api/chat.php?ladder=" + ladderID + "&action=get";
		if(lastUpdate !== null) {
			url += "&from=" + lastUpdate;
		}
		$.getJSON(url, renderChat);
	}
	
	showAllChat = function() {
		url = "api/chat.php?ladder=" + ladderID + "&action=get&from=0";
		$("#chatLines").empty();
		$.getJSON(url, renderChat);
	}
	
	renderChat = function(lines) {
		var tbody = $('#chatLines tbody');
		if(tbody.length == 0) {
			scroll = true;
		} else {
			scroll = tbody.scrollTop() + tbody.height() == tbody.prop("scrollHeight");
		}
		
		html = "";
		$.each(lines, function(i, line) {
			html += "<tr class=\"chatLine\"><td class=\"chatDate\">" + formatDate(line["timestamp"]) + "</td><td class=\"chatName\"><a href=\"player.php?ladder=" + ladderID + "&player=" + line["userID"] + "\">" + line["name"] + "</a></td><td class=\"stretch chatMessage\">" + line["message"] + "</td></tr>";
			lastUpdate = line["timestamp"];
		});
		$("#chatLines").append(html);
		
		if(lastUpdate === null) {
			if($(".noChatLine").length == 0) {
				$("#chatLines").append("<tr class=\"noChatLine\"><td class=\"stretch\" colspan=\"2\"><em>No chat messages.</em></td></tr>");
			}
		} else {
			$(".noChatLine").remove();
		}
		
		if(scroll) {
			var tbody = $('#chatLines tbody');
			tbody.scrollTop(tbody.prop("scrollHeight"));
		}
	}
	
	sendChat = function() {
		line = $("#newChatLine").val();
		$.post("api/chat.php?ladder=" + ladderID + "&action=send", "message=" + encodeURIComponent(line)).done(updateChat);
		$("#newChatLine").val("");
		return false;
	}
	
	init = function() {
		$("#chatForm").submit(sendChat);
		$("#chatShowAll").click(showAllChat)
		
		setInterval(updateChat, 30000);
		updateChat();
	}
	
	formatDate = function(timestamp) {
		formatNumber = function(x) {
			str = x.toString();
			if(str.length == 1) {
				return "0" + str;
			} else {
				return str;
			}
		}
		d = new Date(timestamp*1000);
		months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
		year = d.getFullYear();
		month = months[d.getMonth()];
		date = formatNumber(d.getDate());
		hour = formatNumber(d.getHours());
		min = formatNumber(d.getMinutes());
		return date + ' ' + month + ' ' + year + ' ' + hour + ':' + min;
	}
	
	$(init());
}
