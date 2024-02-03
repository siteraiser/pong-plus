<!doctype html>
<html>
<head>
<style>

.timer{float:right;}

#main div{
	border:1px solid grey;
	padding:10px;
	
}
#main > div{
	border:2px solid green;
	padding:10px;
	border-radius:3px;
}
#main > div > div > div > div{
	border:none;
	padding:10px;
}
#main > button{
	position:relative;
	top:10px;
}
.modal {
	background-color:#fff;
	box-shadow: 10px 10px 5px grey;
	box-sizing: border-box;
	max-width: 500px;
	max-height: 100%;	
	top: 0; 
	left: 0; 
	right: 0; 
	margin-left: auto; 
	margin-right: auto; 
	z-index:1;
	padding: 20px;	
	position: fixed;
	overflow: auto;	
}


.modal label{
	display:block;
}
.modal .close,.modal .clear{
	float:right;
	padding:10px;
	border:1px solid grey;
	cursor: pointer;
	
}
.darken{
	top: 0; bottom: 0; left: 0; right: 0;
	position:fixed;
	background: rgba(0,0,0,.3);
}

.hidden{display:none;}


.modal, #main > div, #messages,#main > div, #transactions{
	 overflow-wrap: break-word;
}
#messages,#transactions{
	z-index:2;
	overflow-y: auto;
    max-height: 100%;
}

.warning{color:red;}


#products_header > div, #menu > div{display:inline-block;vertical-align:middle;}
#products_header,#menu{margin:10px;}
#menu button.selected{color:green;}



table.txns {
	word-break: break-word;
	width: 100%;
}
table.txns,table.txns th, table.txns td {
	border: 1px solid;
}



</style>
</head>
<body>
<div class="timer">
	<button id="pause">Pause</button> - 
	<div id="timer">0</div>
</div>

<div id="menu">
	<div>
		<button id="show_products" class="selected">Products</button>
	</div>
	<div>
		<button id="show_records">Records</button>
	</div>
</div>

<hr>

<div id="messages" class="modal hidden"><div class="close">X</div><div id="message_list"></div></div>
<div id="transactions" class="modal hidden"><div class="clear">clear</div><div class="close">X</div><div id="transactions_list"></div></div>
<div id="products_header">
	<div>
		<button id="show_add_modal">Add Product</button>
	</div>
	<div>
		<button id="show_transactions">Show Transactions</button>
	</div>
	
</div>
<div class= "darken hidden"></div>


<div id="main">
	
</div>

<div id="add_product_modal" class="modal hidden">
	<div class="close">X</div>
	<h2>Add New Product / Service</h2>
	<form>
		<label>Product Label
			<input id="label" name="label" type="text" >
		</label>
		<label>Comment
			<input id="comment" name="comment" type="text" >
		</label>
		<label>Out Message (max 128b)
			<input id="out_message" name="out_message" type="text" maxlength="128">
		</label>
		Only Use UUID <input id="add_out_message_uuid" name="out_message_uuid" type="checkbox" >
		<label>Ask Amount (atomic units)
			<input id="ask_amount" class="atomic_units" name="ask_amount" type="text" ><span class="dero_units"></span>
		</label>
		<label>Respond Amount (atomic units)
			<input id="respond_amount" class="atomic_units" name="respond_amount" type="text" ><span class="dero_units"></span>
		</label>
		<label>Port
			<input id="port" name="port" type="text" >
		</label>
		<br>
		<button role="button" id="add_product">Add Product</button>
	</form>
</div>

<div id="edit_product_modal" class="modal hidden">
	<div class="close">X</div>
	<h2>Edit Product / Service</h2>
	<form>
		<input id="pid" name="pid" type="hidden">
		<label>Product Label
			<input id="label" name="label" type="text" >
		</label>		
		<label>Comment
			<input id="comment" name="comment" type="text" >
		</label>
		<label>Out Message (max 128b)
			<input id="edit_out_message" name="out_message" type="text" maxlength="128">
		</label>
		Only Use UUID <input id="out_message_uuid" name="out_message_uuid" type="checkbox" >
		
		<label>Ask Amount (atomic units)
			<input id="ask_amount" class="atomic_units" name="ask_amount" type="text" ><span class="dero_units"></span>
		</label>
		<label>Respond Amount (atomic units)
			<input id="respond_amount"class="atomic_units" name="respond_amount" type="text" ><span class="dero_units"></span>
		</label>
		<label>Port
			<input id="port" name="port" type="text" >
		</label>
		<br>
		<div id="integrated_addresses">
		</div>
		<button role="button" id="edit_product">Update Product</button>
	</form>
</div>
<script>

//manage the views
var show_records_button = document.getElementById("show_records");
var show_products_button = document.getElementById("show_products");
var products_header = document.getElementById("products_header");

show_products_button.addEventListener("click", (event) => {
	show_products_button.classList.add("selected");	
	show_records_button.classList.remove("selected");
	main.innerHTML = '';	
	products_header.classList.remove("hidden");
	displayProducts(products_array);
})

show_records_button.addEventListener("click", (event) => {
	show_records_button.classList.add("selected");
	show_products_button.classList.remove("selected");
	main.innerHTML = '';
	products_header.classList.add("hidden");
	loadout();
})



/*************************/
/* Show All Transactions */
/*************************/

function loadout() {
	async function getTransactions(data) {
	  try {
		const response = await fetch("/loadout.php", {
		  method: "POST", // or 'PUT'
		  headers: {
        'credentials': 'same-origin',
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/json;charset=utf-8'
        },
		  body: JSON.stringify(data),
		});

		var result = await response.json();			

		main.innerHTML = createTable(result);
		
	  } catch (error) {
		console.error("Error:", error);
	  }
	}

	const data = { mode: "year" };
	getTransactions(data);
}



function createTable(table_data) {
  	var table = '<thead><th>Product Label</th><th>Comment</th><th>Amount</th><th>Out Message</th><th>Response Amt.</th><th>Buyer Address</th><th>Out TXID</th><th>Time UTC</th></thead><tbody>';
	table_data.transactions.forEach(function (val, index, array) {
		let td='';
		td +='<td>'+ val.label + '</td>';
		td +='<td>'+ val.comment + '</td>';
		td +='<td>'+ val.amount + ' (' + niceRound( val.amount * .00001) + ' Dero)' + '</td>';
		td +='<td>'+ val.out_message + '</td>';
		td +='<td>'+ val.out_amount + ' (' + niceRound( val.out_amount * .00001) + ' Dero)' + '</td>';		
		td +='<td>'+ val.buyer_address + '</td>';
		td +='<td>'+ val.txid + '</td>';
		td +='<td>'+ val.time_utc + '</td>';
		
		
		table += '<tr>'+ td + '</tr>';
	});
	
	return '<table class="txns">'+table+'</tbody></table>';
}




/*****************************/
/* Products and I. Addresses */
/*****************************/

var products_array=[];

var main = document.getElementById("main");
var messages = document.getElementById("messages");
var transactions = document.getElementById("transactions");
var show_transactions_button = document.getElementById("show_transactions");

var add_product_modal = document.getElementById("add_product_modal");
var add_product_button = document.getElementById("add_product");
var show_add_modal_button = document.getElementById('show_add_modal');

var edit_product_modal = document.getElementById("edit_product_modal");
var edit_product_button = document.getElementById("edit_product");

var close_buttons = document.querySelectorAll('.close');
var darken_layer = document.querySelector('.darken');
var clear_buttons = document.querySelectorAll('.clear');

var out_messages = document.querySelectorAll('input[name="out_message"]');
var amount_inputs = document.querySelectorAll('.atomic_units');
/* show / hide modals */

show_add_modal_button.addEventListener("click", (event) => {
	add_product_modal.classList.remove("hidden");
	darken_layer.classList.remove("hidden");
});	

close_buttons.forEach((button) => {
	button.addEventListener("click", (event) => {
		event.target.parentElement.classList.add("hidden");
		
		if(event.target.parentElement.id!='messages' && event.target.parentElement.id!='transactions'){
			darken_layer.classList.add("hidden");
		}
	})
});	

clear_buttons.forEach((button) => {
	button.addEventListener("click", (event) => {
		event.target.parentElement.querySelector('#transactions_list').innerHTML = '';
	})
});	


show_transactions_button.addEventListener("click", (event) => {
	transactions.classList.remove("hidden");
})



/* form validation */
function getStringSize(){
	 
	let size =  new Blob([event.target.value]).size;
	
	if(size > 128){
		event.target.classList.add("warning");
	}else{
		event.target.classList.remove("warning");
	}
}

out_messages.forEach((input) => {
input.addEventListener('input', getStringSize, false);
input.addEventListener('keyup', getStringSize, false);
input.addEventListener('blur', getStringSize, false);		
});	


function niceRound(number){
	return Math.round(number*100000000)/100000000;
}
function convert(input){	
	var deri = input.value;
	deri = deri * .00001;
	deri =  niceRound(deri);
	input.parentElement.querySelector('.dero_units').innerHTML = deri+ " Dero";
}
function callConvert(){
	convert(event.target);
}
amount_inputs.forEach((input) => {
input.addEventListener('input', callConvert, false);
input.addEventListener('keyup', callConvert, false);
input.addEventListener('blur', callConvert, false);		
});	

/********************/
/* Display Products */
/********************/
function createSection(section){
	let div = document.createElement('div');
	let text = document.createTextNode(section);
	div.appendChild(text);
	return div;
}
/* load out the products */
function generateProduct(product) {
	
	
	let div = document.createElement('div');

	div.classList.add('product');
	div.setAttribute("data-productid",product.id);
	div.classList.add('product');
	
	let edit = document.createTextNode("Edit Product");
	let button = document.createElement('button'); 
	
	button.addEventListener("click", (event) => {

		editProducts(product.id);
	
		
	});	
	
	button.appendChild(edit);
	main.appendChild(button);
	
	div.appendChild(createSection("Label: " +product.label));	
	div.appendChild(createSection("Respond Amount: " + product.respond_amount + " - (" + niceRound( product.respond_amount * .00001) + " Dero) - Applies to all I. Addrs. for this product"));	
	div.appendChild(createSection("Out Message: " + (product.out_message_uuid ==1 ? "UUID - "+ product.out_message:product.out_message) + " - Applies to all I. Addrs. for this product"));


	var iaddresses = document.createElement('div');
	iaddresses.appendChild(createSection("Integrated Addresses"));
	
	product.iaddress.forEach(function (val, index, array) {
		var iaddress = document.createElement('div');

		iaddress.appendChild(createSection("Integrated Address: " +val.iaddr));
		iaddress.appendChild(createSection("Comment: " +val.comment));
		iaddress.appendChild(createSection("Ask Amount: " +val.ask_amount + " - (" + niceRound( val.ask_amount * .00001) + " Dero)"));
		iaddress.appendChild(createSection("Port: " +val.port));
		let status = "inactive";
		if(val.status == 1){
			status = "active";
		}
		iaddress.appendChild(createSection("Status: " + status));	
		iaddresses.appendChild(iaddress);
		
	});
	
	div.appendChild(iaddresses);
	main.appendChild(div);
	
}


function displayProducts(products){
	  
	products.forEach(generateProduct);

}

/******************/
/* Initialization */
/******************/

function initialize(runit) {
	async function getProducts(data) {
	  try {
		const response = await fetch("/initialize.php", {
		  method: "POST", // or 'PUT'
		  headers: {
        'credentials': 'same-origin',
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/json;charset=utf-8'
        },
		  body: JSON.stringify(data),
		});

		const result = await response.json();			
		//console.log("Success:", result);
		products_array=result.products;
		displayProducts(products_array);
		runit();
		
	  } catch (error) {
		console.error("Error:", error);
	  }
	}

	const data = { action: "initialize" };
	getProducts(data);
}


/****************/
/* Add Products */
/****************/
function addProduct(form) {
	async function submitProduct(form) {
	  try {
		const response = await fetch("/addproduct.php", {
		  method: "POST", // or 'PUT'
		  headers: {
        'credentials': 'same-origin' 
        },
		  body: new FormData(form),
		});

		const result = await response.json();			
	
		if(result.success == false){
			let msgs = '';
			if(typeof result.errors != 'undefined'){
				for(var key in result.errors){
					msgs += result.errors[key] +' ';				
				}
				messages.querySelector("#message_list").innerHTML = msgs;
				messages.classList.remove("hidden");
			}
		
		}else{
			
			products_array.push(result.products[0]);
			main.innerHTML = '';			
			displayProducts(products_array);
			add_product_modal.classList.add("hidden");
			darken_layer.classList.add("hidden");
		}
		
		
	  } catch (error) {
		console.error("Error:", error);
	  }
	}

	submitProduct(form);
}


add_product_button.addEventListener("click", (event) => {
	event.preventDefault();
	let form = event.target.parentElement;

	addProduct(form);
});	





/*****************/
/* Edit Products */
/*****************/


function editProduct(form) {
	async function submitProduct(form) {
		
	  try {
		const response = await fetch("/editproduct.php", {
		  method: "POST", // or 'PUT'
		  headers: {
        'credentials': 'same-origin' 
        },
		  body: new FormData(form),
		});

		const result = await response.json();			

		if(result.success == false){
			let msgs = '';
			if(typeof result.errors != 'undefined'){
				for(var key in result.errors){
					msgs += result.errors[key] +' ';				
				}
				//uncheck the boxes that can't be checked
				for(var key in result.failed_ia_ids){
					let failed_ia_id = result.failed_ia_ids[key];	
					form.querySelector('input[name="iaddress_status['+failed_ia_id+']"]').checked = false;;
				}
				messages.querySelector("#message_list").innerHTML = msgs;
				messages.classList.remove("hidden");
			}
		
		}else{
			main.innerHTML = '';
			products_array = result.products;
			displayProducts(products_array);
			editProducts(form.querySelector("#pid").value);
		}
	
		
	  } catch (error) {
		console.error("Error:", error);
	  }
	}

	submitProduct(form);
	
}

edit_product_button.addEventListener("click", (event) => {
	event.preventDefault();
	let form = event.target.parentElement;

	editProduct(form);
});	




function editProducts(pid) {

	
	var editing = products_array.find(x => x.id == pid);

	edit_product_modal.querySelector("#pid").value = editing.id;
	edit_product_modal.querySelector("#label").value = editing.label;

	edit_product_modal.querySelector("#edit_out_message").classList.remove("warning");
	edit_product_modal.querySelector("#edit_out_message").value = editing.out_message;	
	edit_product_modal.querySelector("#out_message_uuid").checked = (editing.out_message_uuid == 1? true:false);	

	edit_product_modal.querySelector("#respond_amount").value = editing.respond_amount;
	convert(edit_product_modal.querySelector("#respond_amount"));
	
	
	edit_product_modal.querySelector("#integrated_addresses").innerHTML ='';
	let last_ia = editing.iaddress.length -1;
	editing.iaddress.forEach(function (iadd, index, array) {
		//Auto fill using last ia
		if(index == last_ia){
			edit_product_modal.querySelector("#comment").value = iadd.comment;
			edit_product_modal.querySelector("#ask_amount").value = iadd.ask_amount;
			convert(edit_product_modal.querySelector("#ask_amount"));
			edit_product_modal.querySelector("#port").value = iadd.port;	
		}
		
		
		edit_product_modal.querySelector("#integrated_addresses").innerHTML += "Integrated Address: "+iadd.iaddr+"<br>";
		edit_product_modal.querySelector("#integrated_addresses").innerHTML += "Comment: "+iadd.comment+"<br>";
		edit_product_modal.querySelector("#integrated_addresses").innerHTML += "Ask Amount: "+iadd.ask_amount+ " - (" + niceRound( iadd.ask_amount * .00001) + " Dero)"+"<br>";
		edit_product_modal.querySelector("#integrated_addresses").innerHTML += "Port: "+iadd.port+"<br>";
		edit_product_modal.querySelector("#integrated_addresses").innerHTML += "Status Active?: ";
		let checkbox = '<input id="out_message_uuid" name="iaddress_status['+iadd.id+']" '+(iadd.status == 1?"checked":"")+' type="checkbox" >';
		
		edit_product_modal.querySelector("#integrated_addresses").innerHTML += checkbox+"<hr>";;
		
	});
	
	edit_product_modal.classList.remove("hidden");
	darken_layer.classList.remove("hidden");
}



/* start the program */	
window.addEventListener('load', function() {
	initialize(runit);	
});	



/* check for new transactions */
function checkWallet() {
	async function process() {
	  try {
		const response = await fetch("/process.php", {
		  method: "POST", // or 'PUT'
		  headers: {
        'credentials': 'same-origin',
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/json;charset=utf-8'
        },
		  body: JSON.stringify({}),
		});

		const result = await response.json();			

		let msgs = '';
		if(typeof result.errors != 'undefined'){
			for(var key in result.errors){
				msgs += result.errors[key] +'<hr>';				
			}
			
		}
		if(typeof result.messages != 'undefined'){
			for(var key in result.messages){
				msgs += result.messages[key] +'<hr>';				
			}
			
		}
		if(msgs != ''){
			transactions.querySelector("#transactions_list").innerHTML += msgs;
			transactions.classList.remove("hidden");
		}
		
	  } catch (error) {
		console.error("Error:", error);
	  }
	}
	process();
	
}
	
	
/* check for new transactions */
var execute = function() {
	checkWallet();
}


/* timer */
var paused = false;
var pauseButton = document.getElementById('pause');
var alertTimerId =0;
pauseButton.addEventListener("click", (event) => {
	paused = !paused;
	if(paused){
		event.target.innerText = "Paused";
		clearTimeout(alertTimerId);
		clearInterval(running);
	}else{
	  
		event.target.innerText = "Pause";
		running = setInterval(runit, secs * 1000);
		startTimer();
	}
});

var runit = function() {
	if(!paused){
		execute();
		startTimer();
	}	
};	

function startTimer() {
	timer = secs;
	clearTimeout(alertTimerId);
	alertTimerId =  setTimeout(doTime, 1000);  
};	

var secs = 20;
var seconds = secs * 1000;
var running = setInterval(runit, secs * 1000);

var timer = secs;

var timerCountdown = document.getElementById('timer');
function doTime() { 

	var minutes, seconds;
    minutes = parseInt(timer / 60, 10)
    seconds = parseInt(timer % 60, 10);

	minutes = minutes < 10 ? "0" + minutes : minutes;
	seconds = seconds < 10 ? "0" + seconds : seconds;

	timerCountdown.innerText = minutes + ":" + seconds;
	if(!paused){
		if (--timer >= 0) {
			//Call self every second.
			alertTimerId =  	setTimeout(doTime, 1000); 
		}
	}
}

</script>
</body>
</html>
