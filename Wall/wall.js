var express = require('express'), app = express(), http = require('http'), server = http.createServer(app), io = require('socket.io').listen(server), request = require("request");

server.listen(5000);

// Sample url : http://hostname/wordpress/ REMEMBER THE LAST SLASH
//var mainUrl = "http://localhost/WordPressfolder/";
var mainUrl = "http://localhost/wordpress3/";

// Array difference
function arr_diff(a1, a2) {
	var a = [], diff = [];
	for (var i = 0; i < a1.length; i++)
		a[a1[i]] = true;
	for (var i = 0; i < a2.length; i++)
		if (a[a2[i]])
			delete a[a2[i]];
		else
			a[a2[i]] = true;
	for (var k in a)
	diff.push(k);
	return diff;
}

// text shorter
String.prototype.trunc = String.prototype.trunc ||
function(n) {
	return this.length > n ? this.substr(0, n - 1) + '...' : this;
};

// routing

// a convenient variable to refer to the HTML directory
var html_dir = './';

// routes to serve the static HTML files
app.get('/wall', function(req, res) {
	res.sendfile(html_dir + 'visualisation.html');
});

// displaynames which are currently connected to the chat
var displaynames = {};
// display collection length
var displayColLength = {};
// display image collection
var displayImages = {};
// rooms which are currently available in chat
var rooms = ['room1', 'room2', 'room3'];

//updating the news
function wallRetriver(ptype) {
	var $checkIfavailableDisplay = 0;
	for (var entery in displaynames) {
		if (displaynames[entery] == ptype) {
			$checkIfavailableDisplay = $checkIfavailableDisplay + 1;
			break;
			;
		}

	}

	if ($checkIfavailableDisplay >= 1) {

		request({
			url : mainUrl + "wp-content/themes/twentyfourteen/wall.php?ptype=" + ptype,
			json : true
		}, function(error, response, body) {

			if (!error && response.statusCode === 200) {
				if ( typeof body === 'object') {
					var count = Object.keys(body).length;
					console.log(body);
					//console.log(count);

					if (count != 0)
						// console.log(body[Math.floor(Math.random() * body.length)]);

						//Sending the data to the matched displays
						for (var entery in displaynames) {
							if (displaynames[entery] == ptype) {
								io.sockets.connected[entery].emit('updateVisualization', body);
							}
						}

				}
			}
		});
	}
}

function updateWall() {

	var tempDisplayName = [];
	// getting an array of unique values (display names)
	for (var entery in displaynames) {
		tempDisplayName.push(displaynames[entery]);
	}
	tempDisplayName = tempDisplayName.filter(function(e, i, tempDisplayName) {
		return tempDisplayName.lastIndexOf(e) === i;
	});

	// retrieving the data for each wall
	for (var entery in tempDisplayName) {
		wallRetriver(tempDisplayName[entery]);
	}
	// console.log(tempDisplayName);
}

// set interval for 5 sec
setInterval(function() {
	updateWall();
}, 1000 * 21); // 1000 * 1 = 1 second

io.sockets.on('connection', function(socket) {

	// when the client emits 'adddisplay', this listens and executes
	socket.on('adddisplay', function(displayname) {
		// store the displayname in the socket session for this client
		socket.displayname = displayname;
		// store the room name in the socket session for this client
		socket.room = 'room1';
		// add the client's displayname to the global list
		displaynames[socket.id] = displayname;
		// send client to room 1
		socket.join('room1');

		io.sockets.emit('updatedisplays', displaynames);
		wallRetriver(displayname);
		console.log(1);

	});

	// when the display disconnects.. perform this
	socket.on('disconnect', function() {
		if (socket.displayname) {
			// remove the displayname from global displaynames list
			delete displaynames[socket.id];
			delete displayColLength[socket.id];
			// update list of displays in chat, client-side

			io.sockets.emit('updatedisplays', displaynames);
			// echo globally that this client has left

			socket.leave(socket.room);
		}
	});
});
