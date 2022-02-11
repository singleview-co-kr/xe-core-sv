/* global variable */
var g_nPlayingFinishCnt = 0;
var g_fElapsedSecondWhenPaused = 0;
var g_nElapsedSecond = 0;

/* internal variable */
var _g_nPlayCnt = 1;
var _g_nVolume = 5;
var _g_sWidth = '';
var _g_sHeight = '';
var _g_aPlaylist = [];
var _g_oTimer = null;
var _g_bShuffle = false;
var _g_bLoop = false;
var _g_bAutostart = false;

function initPlayer( aId, width, height )
{
	if( jQuery.isArray(aId) === true )
	{
		_g_aPlaylist = aId;
		_g_sWidth = width;
		_g_sHeight = height;
	}
	else
		alert('youtube playlist is not array');
}
function setAutostart() 
{
	_g_bAutostart = true;
}
function setVolume(nVol) 
{
	_g_nVolume = nVol;
}
function setShuffle() 
{
	_g_bShuffle = true;
}
function setLoop() 
{
	_g_bLoop = true;
}

// 2. This code loads the IFrame Player API code asynchronously.
var tag = document.createElement('script');
tag.src = "https://www.youtube.com/iframe_api";
var firstScriptTag = document.getElementsByTagName('script')[0];
firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

// 3. This function creates an <iframe> (and YouTube player)
//    after the API code downloads.
var player;
function onYouTubeIframeAPIReady() {
	player = new YT.Player('player', {
		height: _g_sHeight,
		width: _g_sWidth,
		loadPlaylist:{
			listType:'playlist',
			list:_g_aPlaylist,
			index:parseInt(0),
			suggestedQuality:'small'
		},
		events: {
			'onReady': onPlayerReady,
			'onStateChange': onPlayerStateChange
		}
	});
}

// 4. The API will call this function when the video player is ready.
function onPlayerReady(event) 
{
	event.target.setVolume(_g_nVolume); ////////////////////
	//event.target.loadPlaylist(_g_aPlaylist);
	if( _g_bAutostart )
		event.target.loadPlaylist(_g_aPlaylist);
	else
		event.target.cuePlaylist(_g_aPlaylist);

	//event.target.playVideo();
	
	//event.target.setLoop(true);
	if( _g_bLoop )
		event.target.setLoop(true);

	if( _g_bShuffle )
		event.target.setShuffle(true);
}

function _setShuffleFunction()
{
	player.setShuffle(true);
}
// 5. The API calls this function when the player's state changes.
//    The function indicates that when playing a video (state=1),
//    the player should play for six seconds and then stop.
function onPlayerStateChange(event) 
{
	if( event.data == YT.PlayerState.PLAYING )
		_startMeasureVideo();

	if( event.data == YT.PlayerState.PAUSED )
	{
		g_fElapsedSecondWhenPaused = player.getCurrentTime();
		console.log( 'elapsed time when paused:' + g_fElapsedSecondWhenPaused );
		_stopMeasureVideo();
	}
	if( event.data == YT.PlayerState.ENDED ) 
	{
		g_nPlayingFinishCnt = _g_nPlayCnt++;
		console.log( 'ended' + g_nPlayingFinishCnt );
	}
}

function pauseVideo() 
{
	if( typeof player.pauseVideo === 'function' )
		player.pauseVideo();
}

function playVideo() 
{
	player.playVideo();
}

function _startMeasureVideo() 
{
	_g_oTimer = setTimeout( function(){
		g_nElapsedSecond = Math.round( player.getCurrentTime() );
		//document.getElementById('log').innerHTML += '<br>'+g_nElapsedSecond;
		_startMeasureVideo();
	}, 1000);
}

function _stopMeasureVideo() 
{
	if (_g_oTimer) 
	{
		clearTimeout(_g_oTimer);
		_g_oTimer = 0;
	}
}