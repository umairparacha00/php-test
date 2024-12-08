<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- CSRF Token for Ajax -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .editable {
            width: 100%;
        }
    </style>
</head>
<body class="p-3">
<div class="container">
    <h1 class="mb-4">Add product</h1>
    <!-- Error message container -->
    <div id="errorMessages" class="alert alert-danger" style="display:none;"></div>

    <form id="productForm" class="mb-5">
        <div class="row mb-3">
            <div class="col-12 col-md-4 mb-2">
                <label for="name" class="form-label">Product Name</label>
                <input type="text" name="name" id="name" class="form-control" required>
            </div>
            <div class="col-12 col-md-4 mb-2">
                <label for="quantity_in_stock" class="form-label">Quantity in Stock</label>
                <input type="number" name="quantity_in_stock" id="quantity_in_stock" class="form-control" required>
            </div>
            <div class="col-12 col-md-4 mb-2">
                <label for="price_per_item" class="form-label">Price per Item</label>
                <input type="number" step="0.01" name="price_per_item" id="price_per_item" class="form-control" required>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Create</button>
    </form>

    <h2>Products</h2>
    <div class="table-responsive">
        <table class="table table-bordered table-striped" id="productsTable">
            <thead>
            <tr>
                <th>Product Name</th>
                <th>Quantity in stock</th>
                <th>Price per item</th>
                <th>Datetime submitted</th>
                <th>Total value number</th>
                <th>Edit</th>
            </tr>
            </thead>
            <tbody>
            @php $sumTotalValue = 0; @endphp
            @foreach($products as $index => $product)
                @php
                    $totalValue = $product['quantity_in_stock'] * $product['price_per_item'];
                    $sumTotalValue += $totalValue;
                @endphp
                <tr>
                    <td>{{ $product['name'] }}</td>
                    <td>{{ $product['quantity_in_stock'] }}</td>
                    <td>{{ $product['price_per_item'] }}</td>
                    <td>{{ $product['datetime_submitted'] }}</td>
                    <td>{{ $totalValue }}</td>
                    <td>
                        <button class="btn btn-sm btn-warning edit-btn" data-index="{{ $index }}">Edit</button>
                        <button class="btn btn-sm btn-danger delete-btn" data-index="{{ $index }}">Delete</button>
                    </td>
                </tr>
            @endforeach
            </tbody>
            <tfoot>
            <tr>
                <th colspan="4" class="text-end">Sum Total:</th>
                <th id="sumTotalValue">{{ $sumTotalValue }}</th>
                <th></th>
            </tr>
            </tfoot>
        </table>
    </div>
</div>

<!-- jQuery and Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });

    // Handle form submission via Ajax for creating new products
    $('#productForm').on('submit', function(e) {
        e.preventDefault();
        $('#errorMessages').hide().empty();

        let formData = {
            name: $('#name').val(),
            quantity_in_stock: $('#quantity_in_stock').val(),
            price_per_item: $('#price_per_item').val()
        };

        $.ajax({
            type: 'POST',
            url: "{{ route('products.store') }}",
            data: formData,
            dataType: 'json',
            success: function(response) {
                if(response.status === 'success') {
                    updateTable(response.data);
                    clearAddProductForm();
                }
            },
            error: function(xhr) {
                handleFormErrors(xhr, $('#errorMessages'));
            }
        });
    });

    // Edit button click
    $(document).on('click', '.edit-btn', function(){
        let row = $(this).closest('tr');
        let index = $(this).data('index');

        // If already editing, do nothing
        if(row.hasClass('editing')) {
            return;
        }

        row.addClass('editing');
        let productName = row.find('td:eq(0)').text();
        let quantity = row.find('td:eq(1)').text();
        let price = row.find('td:eq(2)').text();

        // Convert cells to inputs
        row.find('td:eq(0)').html('<input type="text" class="form-control editable" value="'+ productName +'">');
        row.find('td:eq(1)').html('<input type="number" class="form-control editable" value="'+ quantity +'">');
        row.find('td:eq(2)').html('<input type="number" step="0.01" class="form-control editable" value="'+ price +'">');

        $(this).text('Save')
            .removeClass('btn-warning edit-btn')
            .addClass('btn-success save-btn')
            .data('index', index);
    });

    // Save button click for inline editing
    $(document).on('click', '.save-btn', function(){
        let row = $(this).closest('tr');
        let index = $(this).data('index');
        let productName = row.find('td:eq(0) input').val();
        let quantity = row.find('td:eq(1) input').val();
        let price = row.find('td:eq(2) input').val();

        // Clear global and inline errors before new request
        $('#errorMessages').hide().empty();
        clearInlineErrors(row);

        let formData = {
            index: index,
            name: productName,
            quantity_in_stock: quantity,
            price_per_item: price
        };

        $.ajax({
            type: 'POST',
            url: "{{ route('products.update') }}",
            data: formData,
            dataType: 'json',
            success: function(response) {
                if(response.status === 'success') {
                    updateTable(response.data);
                }
            },
            error: function(xhr) {
                if(xhr.status === 422) {
                    let errors = xhr.responseJSON.errors;

                    // Display global error messages if desired
                    let errorHtml = '<ul>';
                    $.each(errors, function(key, value) {
                        errorHtml += '<li>' + value[0] + '</li>';
                    });
                    errorHtml += '</ul>';
                    $('#errorMessages').html(errorHtml).fadeIn();

                    // Display inline errors next to each field that has an error
                    displayInlineErrors(row, errors);
                } else {
                    $('#errorMessages').html('<p>An unexpected error occurred. Please try again.</p>').fadeIn();
                }
            }
        });
    });


    // Delete button click
    $(document).on('click', '.delete-btn', function() {
        let row = $(this).closest('tr');
        let index = $(this).data('index');

        // Clear error messages
        $('#errorMessages').hide().empty();

        if(!confirm('Are you sure you want to delete this product?')) {
            return;
        }

        $.ajax({
            type: 'DELETE',
            url: "{{ url('products') }}/" + index,
            dataType: 'json',
            success: function(response) {
                if(response.status === 'success') {
                    updateTable(response.data);
                }
            },
            error: function(xhr) {
                if(xhr.status === 404) {
                    $('#errorMessages').html('<p>Product not found.</p>').fadeIn();
                } else {
                    $('#errorMessages').html('<p>An unexpected error occurred. Please try again.</p>').fadeIn();
                }
            }
        });
    });

    function updateTable(data) {
        let tbody = $('#productsTable tbody');
        tbody.empty();
        let sumTotal = 0;

        $.each(data, function(i, product){
            let totalValue = product.quantity_in_stock * product.price_per_item;
            sumTotal += totalValue;
            let row = '<tr>' +
                '<td>'+ product.name +'</td>' +
                '<td>'+ product.quantity_in_stock +'</td>' +
                '<td>'+ product.price_per_item +'</td>' +
                '<td>'+ product.datetime_submitted +'</td>' +
                '<td>'+ totalValue +'</td>' +
                '<td>' +
                '<button class="btn btn-sm btn-warning edit-btn" data-index="'+ i +'">Edit</button> ' +
                '<button class="btn btn-sm btn-danger delete-btn" data-index="'+ i +'">Delete</button>' +
                '</td>' +
                '</tr>';
            tbody.append(row);
        });

        $('#sumTotalValue').text(sumTotal);
    }

    function handleFormErrors(xhr, errorContainer) {
        if(xhr.status === 422) {
            let errors = xhr.responseJSON.errors;
            let errorHtml = '<ul>';
            $.each(errors, function(key, value) {
                errorHtml += '<li>' + value[0] + '</li>';
            });
            errorHtml += '</ul>';
            errorContainer.html(errorHtml).fadeIn();
        } else {
            errorContainer.html('<p>An unexpected error occurred. Please try again.</p>').fadeIn();
        }
    }

    function clearInlineErrors(row) {
        // This will remove any existing in line error
        row.find('.inline-error').remove();
    }

    function clearAddProductForm() {
        $('#name').val('');
        $('#quantity_in_stock').val('');
        $('#price_per_item').val('');
    }

    function displayInlineErrors(row, errors) {
        let fieldMap = {
            'name': 0,
            'quantity_in_stock': 1,
            'price_per_item': 2
        };

        $.each(errors, function(field, messages) {
            let cellIndex = fieldMap[field];
            if(cellIndex !== undefined) {
                let cell = row.find('td').eq(cellIndex);
                let errorMsg = '<div class="text-danger inline-error small">' + messages[0] + '</div>';
                cell.append(errorMsg);
            }
        });
    }

</script>
</body>
</html>
