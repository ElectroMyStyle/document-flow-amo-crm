@extends('layouts.main')

@section('head-title', 'Главная')

@section('content')
    <div class="container">
        <div class="row mt-3">
            <div class="col">
                <p>Нет обработанных документов</p>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col">
                {{--<table class="table">
                    <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Title</th>
                        <th scope="col">Content</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <th scope="row">1</th>
                        <td>Mark</td>
                        <td>Otto</td>
                    </tr>
                    <tr>
                        <th scope="row">2</th>
                        <td>Jacob</td>
                        <td>Thornton</td>
                    </tr>
                    </tbody>
                </table>--}}
            </div>
        </div>
    </div>
@endsection
