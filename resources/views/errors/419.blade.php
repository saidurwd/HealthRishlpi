@extends('errors.layout')

@section('code', '419')
@section('message', $exception->getMessage() ?: 'Your session has expired. Please go back, refresh the page and try again.')
