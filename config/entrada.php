<?php

function textoEntrada($valor): string {
    return is_string($valor) && mb_check_encoding($valor, "UTF-8") ? trim($valor) : "";
}

function precioEntradaValido(string $precio): bool {
    return preg_match('/\A[0-9]{1,8}(?:\.[0-9]{1,2})?\z/', $precio) === 1;
}
