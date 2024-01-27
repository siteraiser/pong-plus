<!doctype html>
<html>
<head>
<style>
.header > div{display:inline-block;vertical-align:middle;}

#products div{
	border:1px solid grey;
	padding:10px;
	
}
#products > div{
	border:2px solid green;
	padding:10px;
	border-radius:3px;
}
#products > div > div > div > div{
	border:none;
	padding:10px;
}
#products > button{
	position:relative;
	top:10px;
}
.modal {
	background-color:#fff;
	box-shadow: 10px 10px 5px grey;
	max-width:500px;

	top: 0; 
	left: 0; 
	right: 0; 
	margin-left: auto; 
	margin-right: auto; 
	z-index:1;
	padding:20px;
	
	
	position: fixed; /* Stay in place */
  z-index: 1; /* Sit on top */

  overflow: auto; /* Enable scroll if needed */
	
	
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


.modal, #products > div, #messages,#products > div, #transactions{
	 overflow-wrap: break-word;
}
#messages,#transactions{
	z-index:2;
	overflow-y: auto;
    max-height: 100%;
}
</style>
</head>
<body>
<div id="messages" class="modal hidden"><div class="close">X</div><div id="message_list"></div></div>
<div id="transactions" class="modal hidden"><div class="clear">clear</div><div class="close">X</div><div id="transactions_list"></div></div>
<div class="header">
	<div>
		<button id="show_add_modal">Add Product</button>
	</div>
	<div>
		<button id="pause">Pause</button> - 
		<div id="timer">0</div>
	</div>
</div>
<div class= "darken hidden"></div>

<div id="products"></div>


<div id="add_product_modal" class="modal hidden">
	<div class="close">X</div>
	<h2>Add New Product / Service</h2>
	<form>
		<label>Comment
			<input id="comment" name="comment" type="text" >
		</label>
		<label>Out Message
			<input id="out_message" name="out_message" type="text" >
		</label>
		Only Use UUID <input id="add_out_message_uuid" name="out_message_uuid" type="checkbox" >
		<label>Ask Amount
			<input id="ask_amount" name="ask_amount" type="text" >
		</label>
		<label>Respond Amount
			<input id="respond_amount" name="respond_amount" type="text" >
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
		<label>Comment
			<input id="comment" name="comment" type="text" >
		</label>
		<label>Out Message
			<input id="out_message" name="out_message" type="text" >
		</label>
		Only Use UUID <input id="out_message_uuid" name="out_message_uuid" type="checkbox" >
		
		<label>Ask Amount
			<input id="ask_amount" name="ask_amount" type="text" >
		</label>
		<label>Respond Amount
			<input id="respond_amount" name="respond_amount" type="text" >
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
var products_array=[];

var products = document.getElementById("products");
var messages = document.getElementById("messages");
var transactions = document.getElementById("transactions");

var add_product_modal = document.getElementById("add_product_modal");
var add_product_button = document.getElementById("add_product");
var show_add_modal_button = document.getElementById('show_add_modal');

var edit_product_modal = document.getElementById("edit_product_modal");
var edit_product_button = document.getElementById("edit_product");

var close_buttons = document.querySelectorAll('.close');
var darken_layer = document.querySelector('.darken');

var clear_buttons = document.querySelectorAll('.clear');

/* show / hide modals */

show_add_modal_button.addEventListener("click", (event) => {
	add_product_modal.classList.remove("hidden");
	darken_layer.classList.remove("hidden");
});	

close_buttons.forEach((button) => {
	button.addEventListener("click", (event) => {
		event.target.parentElement.classList.add("hidden");
		
		if(event.target.parentElement.id!='messages'){
			darken_layer.classList.add("hidden");
		}
	})
});	

clear_buttons.forEach((button) => {
	button.addEventListener("click", (event) => {
		event.target.parentElement.querySelector('#transactions_list').innerHTML = '';
	})
});	
/*
let out_message_uuid = document.getElementById("out_message_uuid");

edit_out_message_uuid.addEventListener("change", function (){
	
	var disable=false;
	if(edit_out_message_uuid.checked ){
		disable = true;
	}	
	document.getElementById("start_hours").disabled = disable;
});
*/


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
	
	console.log(product);
	
	let div = document.createElement('div');
	//let br = document.createElement('br');
	div.classList.add('product');
	div.setAttribute("data-productid",product.id);
	div.classList.add('product');
	
	let edit = document.createTextNode("Edit Product");
	let button = document.createElement('button'); 
	
	
	console.log("wtf:"+product.id);
	button.addEventListener("click", (event) => {
		//let form = event.target.parentElement;
	
		editProducts(product.id);
	
		
	});	
	
	button.appendChild(edit);
	products.appendChild(button);
	
	div.appendChild(createSection("Comment: " +product.comment));	
	div.appendChild(createSection("Ask Amount: " + product.ask_amount));
	div.appendChild(createSection("Respond Amount: " + product.respond_amount));	
	div.appendChild(createSection("Out Message: " + (product.out_message_uuid ==1 ? "UUID":product.out_message)));

	div.appendChild(createSection("Port: " + product.port));

	var iaddresses = document.createElement('div');
	iaddresses.appendChild(createSection("Integrated Addresses"));
	
	product.iaddress.forEach(function (val, index, array) {
		var iaddress = document.createElement('div');

		iaddress.appendChild(createSection("Integrated Address: " +val.iaddr));
		iaddress.appendChild(createSection("Comment: " +val.comment));
		iaddress.appendChild(createSection("Ask Amount: " +val.ask_amount));
		iaddress.appendChild(createSection("Port: " +val.port));
		let status = "inactive";
		if(val.status == 1){
			status = "active";
		}
		iaddress.appendChild(createSection("Status: " + status));	
		iaddresses.appendChild(iaddress);
		
	});
	
	div.appendChild(iaddresses);
	products.appendChild(div);
	
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
		const response = await fetch("//localhost/initialize.php", {
		  method: "POST", // or 'PUT'
		  headers: {
        'credentials': 'same-origin',
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/json;charset=utf-8'
        },
		  body: JSON.stringify(data),
		});

		const result = await response.json();			
		console.log("Success:", result);
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
		const response = await fetch("//localhost/addproduct.php", {
		  method: "POST", // or 'PUT'
		  headers: {
        'credentials': 'same-origin' 
        },
		  body: new FormData(form),
		});

		const result = await response.json();			
		console.log("Success:", result);
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
			products.innerHTML = '';			
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
	console.log(form);
	addProduct(form);
});	





/*****************/
/* Edit Products */
/*****************/


function editProduct(form) {
	async function submitProduct(form) {
	  try {
		const response = await fetch("//localhost/editproduct.php", {
		  method: "POST", // or 'PUT'
		  headers: {
        'credentials': 'same-origin' 
        },
		  body: new FormData(form),
		});

		const result = await response.json();			
		console.log("Success:", result);
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
			products.innerHTML = '';
			products_array = result.products;
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

edit_product_button.addEventListener("click", (event) => {
	event.preventDefault();
	let form = event.target.parentElement;
	console.log(form);
	editProduct(form);
});	




function editProducts(pid) {

	
	var editing = products_array.find(x => x.id === pid);
	
	edit_product_modal.querySelector("#pid").value = editing.id;
	edit_product_modal.querySelector("#comment").value = editing.comment;
	edit_product_modal.querySelector("#out_message").value = editing.out_message;	
	edit_product_modal.querySelector("#out_message_uuid").checked = (editing.out_message_uuid == 1? true:false);	
	edit_product_modal.querySelector("#ask_amount").value = editing.ask_amount;
	edit_product_modal.querySelector("#respond_amount").value = editing.respond_amount;
	edit_product_modal.querySelector("#port").value = editing.port;	
	
	edit_product_modal.querySelector("#integrated_addresses").innerHTML ='';
	editing.iaddress.forEach(function (iadd, index, array) {
		edit_product_modal.querySelector("#integrated_addresses").innerHTML += "Integrated Address: "+iadd.iaddr+"<br>";
		edit_product_modal.querySelector("#integrated_addresses").innerHTML += "Ask Amount: "+iadd.ask_amount+"<br>";
		edit_product_modal.querySelector("#integrated_addresses").innerHTML += "Port: "+iadd.port+"<br>";
		edit_product_modal.querySelector("#integrated_addresses").innerHTML += "Status Active?: ";
		let checkbox = '<input id="out_message_uuid" name="iaddress_status['+iadd.id+']" '+(iadd.status == 1?"checked":"")+' type="checkbox" >';
		
		edit_product_modal.querySelector("#integrated_addresses").innerHTML += checkbox;
		
	});
	//edit_product_modal.querySelector("#integrated_addresses") = editing.port;	
	
	//'<input id="out_message_uuid" name="out_message_uuid" type="checkbox" >'
	
	
	
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
		const response = await fetch("//localhost/process.php", {
		  method: "POST", // or 'PUT'
		  headers: {
        'credentials': 'same-origin',
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/json;charset=utf-8'
        },
		  body: JSON.stringify({}),
		});

		const result = await response.json();			
		console.log("Success:", result);

		
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