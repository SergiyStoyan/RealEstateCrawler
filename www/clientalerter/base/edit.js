//********************************************************************************************
//Author: Sergey Stoyan, CliverSoft.com
//        http://cliversoft.com
//        stoyan@cliversoft.com
//        sergey.stoyan@gmail.com
//        27 February 2007
//Copyright: (C) 2007, Sergey Stoyan
//********************************************************************************************


function getResource(uri, data_callback, error_callback, timeout, tag) 
{
    var tryAgain = function() {
        getResource(uri, data_callback, error_callback, timeout, tag);
    }
    try {
        var r = new XMLHttpRequest();
    } catch (e) {
        alert("Your browser does not suport AJAX!");
        return false;
    }
    var timer = setTimeout(
    function() {
        r.abort();
        r.onreadystatechange = null;
        setTimeout(tryAgain, timeout);
    },
    timeout);
    r.open("GET", uri, true);
    r.onreadystatechange = function() {
        if (r.readyState != 4) {
            // Ignore non-loaded readyStates
            // ...will timeout if do not get to "Loaded"
            return;
        }
        clearTimeout(timer);  // readyState==4, no more timer
        if (r.status == 200) {  // "OK status"
            data_callback(r.responseText, tag);
        }
        else if (r.status == 304) {
            // "Not Modified": No change to display
        }
        else if (r.status >= 400 && r.status < 500) {
            // Client error, probably bad URI
            error_callback(r, tag)
        }
        else if (r.status >= 500 && r.status < 600) {
            // Server error, try again after delay
            setTimeout(tryAgain, timeout);
        }
        else {
            error_callback(r);
        }
    }
    r.send();
    return r;
}

function Save(save_url, table, field, value, key_id, edited_element)
{
	var tag = [edited_element, edited_element.className];	
	edited_element.className += " edit"; 
    getResource(
		save_url + "?table=" + table + "&field=" + urlencode(field) + "&value=" + urlencode(value) + "&key_id=" + urlencode(key_id),
        function(page, tag)
        {
		    edited_element = tag[0];
		    page = trim(page);
			if(page != "OK") alert("Error:\n" + page + "\n\nData could not be saved.");
			else edited_element.innerHTML = value; 
	        edited_element.className = tag[1]; 
	    },
        function(r, tag)
        {
            alert("Could not connect to the server!");
		    edited_element = tag[0];
	        edited_element.className = tag[1]; 
        },
        30000,
        tag 
    );
}

/*function Save(save_url, table, field, value, key_id, edited_element, edited_html)
{
	var tag = [edited_element, edited_html];    
    getResource(
		save_url + "?table=" + table + "&field=" + urlencode(field) + "&value=" + urlencode(value) + "&key_id=" + urlencode(key_id),
        function(page, tag)
        { 
		edited_element = tag[0];
		edited_html = tag[1];
		page = trim(page);
			if(page != "OK"){
				alert("Error:\n" + page + "\n\nData could not be saved.");
	    		return;
			}
	        if(is_defined(edited_element)) edited_element.innerHTML = edited_html; 
	    },
        function(r, tag)
        {
            alert("Could not connect to the server!");
        },
        30000,
        tag 
    );
}*/

function save(edit_key, value)
{
    save_url = _edit_stack[edit_key]["save_url"];
    field = _edit_stack[edit_key]["field"];
    key_id = _edit_stack[edit_key]["key_id"];
    table = _edit_stack[edit_key]["table"];
    
    getResource(
		save_url + "?table=" + table + "&field=" + urlencode(field) + "&value=" + urlencode(value) + "&key_id=" + urlencode(key_id),
        function(page, edit_key)
        {
		page = trim(page);
			if(page != "OK"){
				alert("Error:\n" + page + "\n\nData could not be saved.");
	    		return;
			}
	        _edit_stack[edit_key]["edited_element"].innerHTML = value; 
	        closeDialogBox(edit_key);
	    },
        function(r, edit_key)
        {
            alert("Could not connect to the server!");
	        closeDialogBox(edit_key);
        },
        30000,
        edit_key
    );
}

function validate(edit_key, value)
{
    validate_callback = _edit_stack[edit_key]["validate_callback"];
    if(!is_defined(validate_callback)) return true;
    if(validate_callback(value)) return true;
    return false;
}

function Edit(editor_html, save_url, table, field, key_id, edited_element, validate_callback)
{    edit_key = "k" + Math.random();	_edit_stack[edit_key] = new Array();
    _edit_stack[edit_key]["table"] = table;
	_edit_stack[edit_key]["save_url"] = save_url;
	_edit_stack[edit_key]["field"] = field;
	_edit_stack[edit_key]["key_id"] = key_id;
	_edit_stack[edit_key]["edited_element"] = edited_element;
	_edit_stack[edit_key]["edited_element"].style.display = "none";
	_edit_stack[edit_key]["validate_callback"] = validate_callback;
	_onchange = " onchange='if(!validate(\"" + edit_key + "\", this.value)) return false; save(\"" + edit_key + "\", this.value);'";
	r = new RegExp("<.*?( |>)", "i");
	var m = r.exec(editor_html);
	editor_html = editor_html.substr(0, m[0].length - m[1].length) + _onchange + editor_html.substr(m[0].length - m[1].length);
	showDialogBox(editor_html, edit_key);
}

var _edit_stack = new Array();

function showDialogBox(editor_html, edit_key) {
	html = "<table><tr>";
	html += "<td>" + editor_html + "</td>";
	html += "<td align='right' style='border-left: #000000 1px solid;'>&nbsp;<a style='font-style: normal;' href='#' onclick='closeDialogBox(\"" + edit_key + "\"); return false;'>X</a></td>";
	html += "</tr></table>";
	_edit_stack[edit_key]["editor_element"] = document.createElement("div");
	//_edit_stack[edit_key]["editor_element"].setAttribute("width", "10px");
	_edit_stack[edit_key]["editor_element"].innerHTML = html;
	_edit_stack[edit_key]["edited_element"].parentNode.appendChild(_edit_stack[edit_key]["editor_element"]);
}

function closeDialogBox(edit_key) 
{
    //if(!is_defined(_edit_stack[edit_key]["edited_element"]) return;
	_edit_stack[edit_key]["edited_element"].parentNode.removeChild(_edit_stack[edit_key]["editor_element"]);
	_edit_stack[edit_key]["edited_element"].style.display = "block";
	delete _edit_stack[edit_key];
}





function trim(str) 
{
    return str.replace(/^\s*/, "").replace(/\s*$/, "");
}

function is_defined(variable) 
{
    return typeof(variable) != 'undefined';
}

function is_numeric(variable)
{
   return (variable - 0) == variable && variable.length > 0;
}

function urlencode (str) {
    var hexStr = function (dec) {
        return '%' + (dec < 16 ? '0' : '') + dec.toString(16).toUpperCase();
    };

    var ret = '',
            unreserved = /[\w.-]/; // A-Za-z0-9_.- // Tilde is not here for historical reasons; to preserve it, use rawurlencode instead
    str = (str+'').toString();

    for (var i = 0, dl = str.length; i < dl; i++) {
        var ch = str.charAt(i);
        if (unreserved.test(ch)) {
            ret += ch;
        }
        else {
            var code = str.charCodeAt(i);
            if (0xD800 <= code && code <= 0xDBFF) { // High surrogate (could change last hex to 0xDB7F to treat high private surrogates as single characters); https://developer.mozilla.org/index.php?title=en/Core_JavaScript_1.5_Reference/Global_Objects/String/charCodeAt
                ret += ((code - 0xD800) * 0x400) + (str.charCodeAt(i+1) - 0xDC00) + 0x10000;
                i++; // skip the next one as we just retrieved it as a low surrogate
            }
            // We never come across a low surrogate because we skip them, unless invalid
            // Reserved assumed to be in UTF-8, as in PHP
            else if (code === 32) {
                ret += '+'; // %20 in rawurlencode
            }
            else if (code < 128) { // 1 byte
                ret += hexStr(code);
            }
            else if (code >= 128 && code < 2048) { // 2 bytes
                ret += hexStr((code >> 6) | 0xC0);
                ret += hexStr((code & 0x3F) | 0x80);
            }
            else if (code >= 2048) { // 3 bytes (code < 65536)
                ret += hexStr((code >> 12) | 0xE0);
                ret += hexStr(((code >> 6) & 0x3F) | 0x80);
                ret += hexStr((code & 0x3F) | 0x80);
            }
        }
    }
    return ret;
}




