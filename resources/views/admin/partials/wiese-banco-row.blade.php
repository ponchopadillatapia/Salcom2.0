<tr class="captura">
    {{-- Fecha precargada a HOY: solo se da Enter para confirmar (pedido de dirección). --}}
    <td class="c-date"><input class="qk-cell" data-col="date" type="text" autocomplete="off" aria-label="Date" placeholder="dd/mm/aaaa" value="{{ now()->format('d/m/Y') }}"></td>
    {{-- num: PRECARGADO con el siguiente consecutivo (visual). readonly: lo confirma el servidor al guardar. --}}
    <td class="c-num"><input class="qk-cell" data-col="num" type="text" autocomplete="off" aria-label="Num" readonly tabindex="-1" value="{{ $siguienteNum ?? '' }}"></td>
    <td class="c-payee">
        <div class="qk-payee-wrap">
            <input class="qk-cell" data-col="payee" type="text" autocomplete="off" aria-label="Payee">
            <input class="qk-cell" data-col="category" type="text" autocomplete="off" aria-label="Category">
        </div>
    </td>
    <td class="c-memo"><input class="qk-cell" data-col="memo" type="text" autocomplete="off" aria-label="Memo"></td>
    <td class="c-payment"><input class="qk-cell r" data-col="payment" type="text" inputmode="decimal" autocomplete="off" aria-label="Payment"></td>
    <td class="c-deposit"><input class="qk-cell r" data-col="deposit" type="text" inputmode="decimal" autocomplete="off" aria-label="Deposit"></td>
    {{-- balance: lo calcula el servidor. readonly. --}}
    <td class="c-balance"><input class="qk-cell r" data-col="balance" type="text" autocomplete="off" aria-label="Balance" readonly tabindex="-1"></td>
</tr>
