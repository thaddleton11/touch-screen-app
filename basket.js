$(function () {
    $('.toast').toast()

    $('.basket-btn').click(function () {
        let id = $(this).data("id");
        addToBasket(id);
    })

    $('html').on('click', '.remove-btn', function(){
        let id = $(this).data("id");
        removeFromBasket(id)
    });


    getNewBasket().then(() => {
        buttonUpdater()
    });

    $('#ccs-toaster').on('hidden.bs.toast', function () {
        $(this).children().remove();
    })

    $('#ccsBasketDropdown').click(function(){
        $('#basket').toggleClass('d-block');
    })
    $('body').click(function(e){
        console.log(e.target.className)
        if(!e.target.matches("#ccsBasketIcon") && !e.target.matches(".remove-btn")){
            $('#basket').removeClass('d-block');
        }
    })
});
const _blank = () => {};

async function addToBasket(id) {
    let _current = await current();

    console.log(_current)
    if (Array.isArray(_current) && !_current.includes(id)) {
        _current.push(id);

        sessionStorage.setItem("basket", JSON.stringify(_current));

        getNewBasket().then(function (data) {
console.log(data)
            // toast
            data.forEach((value) => {
                if(value.guid === id)
                    basketToaster(value);
            });

            // change button
            $('.basket-btn[data-id="' + id + '"]').addClass("d-none")
            $('.remove-btn[data-id="' + id + '"]').removeClass("d-none")

        }, function () {
            console.error("Add to basket error")

        });
    }




}


async function removeFromBasket(id) {
    let _current = await current();

    _current = _current.filter(function(item){
        return item !== id
    });

    sessionStorage.setItem("basket", JSON.stringify(_current));

    getNewBasket().then(function (data) {
        removeToaster();

        // change button
        $('.basket-btn[data-id="' + id + '"]').removeClass("d-none")
        $('.remove-btn[data-id="' + id + '"]').addClass("d-none")

    }, function () {
        console.error("Adding to basket")

    });
}



async function getNewBasket() {

    let _current = await current();
    if (Array.isArray(_current)) {
        console.log({_current})
        let response = postData('/api/basket/update', {_current});
        return response.then(data => {
            console.log('Success:', data);
            document.querySelector('meta[name="jsToken"]').content = data.csrf_token;

            if(!data.error) {
                basketBuilder(data.data);
                // buttonUpdater()
                return data.data;
            } else {
                emptyBasket();
                return false;
            }
        })
            .catch((error) => {
                console.error('Error:', error);
            });


        /*$.post(, {_current}, await function (data, status) {
            alert("Data: " + data + "\nStatus: " + status);
            console.log(data)
            if(data && status === "success"){

                basketBuilder(data);
                return data;
            }

            return false;
        })*/
    }

    return [];
}


async function current() {
    let _current = sessionStorage.basket;
    console.log(_current)
    if (_current !== undefined) {
        return JSON.parse(_current);
    } else {
        return [];
    }
}


function basketBuilder(data) {

    let html = `
        ${data.map(item => `
            <div class="media border-bottom pb-2 pt-2">
                <i class="fa fa-file-pdf fa-2x mr-4 text-secondary"></i>
                <div class="media-body d-flex align-items-center">
                    <p class="m-0">${item.title}</p>
                    <button class="btn btn-link ml-auto" data-id="${item.guid}">
                        <i class="fa fa-times text-danger remove-btn" data-id="${item.guid}"></i>
                    </button>
                </div>
                
            </div>
        `).join('')}
        `;
    let btn = `<a href="/checkout" class="btn btn-primary mt-4 ml-auto">Checkout now <i class="fa fa-paper-plane"></i></a>`;
    $('.basket-items').html(html).append(btn);

    $('#ccsBasketDropdown .badge').html(data.length ?? 0);
}
function emptyBasket() {

    let html = `
        <h5>You have no items in your basket. Visit the <a href="/resources/buyers">resources</a>.</h5>
        `;
    $('.basket-items').html(html);

    $('#ccsBasketDropdown .badge').html(0);
}



function basketToaster(data) {
    let html = `
    <div class="toast" role="alert" aria-live="assertive" aria-atomic="true" data-delay="2500">
      <div class="toast-header">
        <h4 class="mr-auto">Your Basket</h4>
        <button type="button" class="ml-2 mb-1 close" data-dismiss="toast" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="toast-body">
        ${data.title} was added to the basket. Go to <a href="/basket">your basket now?</a>
      </div>
    </div>
    `;

    $('#ccs-toaster').append(html); 
    $('.toast').toast('show')
}

function removeToaster() {
    let html = `
    <div class="toast" role="alert" aria-live="assertive" aria-atomic="true" data-delay="2500">
      <div class="toast-header">
        <h4 class="mr-auto">Your Basket</h4>
        <button type="button" class="ml-2 mb-1 close" data-dismiss="toast" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="toast-body">
        That item has been removed from the basket. Go to <a href="/basket">your basket now?</a>
      </div>
    </div>
    `;

    $('#ccs-toaster').append(html);
    $('.toast').toast('show')
}



function buttonUpdater() {
    current().then(data => {
        if(data){
            data.forEach(function(d){
                $('.remove-btn[data-id="' + d + '"]').removeClass("d-none")
                $('.basket-btn[data-id="' + d + '"]').addClass("d-none")
            })


        }
    })
}


async function postData(url = '', data = {}) {
    // Default options are marked with *
    data.csrf_token = document.querySelector('meta[name="jsToken"]').content;

    const response = await fetch(url, {
        method: 'POST', // *GET, POST, PUT, DELETE, etc.
        headers: {
            'Content-Type': 'application/json'
            // 'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: JSON.stringify(data) // body data type must match "Content-Type" header
    });

    return response.json(); // parses JSON response into native JavaScript objects
}