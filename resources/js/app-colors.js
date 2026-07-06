export const colorPalette = [
    "#0153ff",  // Azul oscuro
    "#ddd57b",  // Amarillo
    "#ff5733",  // Naranja vibrante
    "#8e44ad",  // Púrpura
    "#27ae60",  // Verde
    "#e74c3c",  // Rojo
    "#f1c40f",  // Amarillo intenso
    "#3498db",  // Azul claro
    "#e67e22",  // Naranja oscuro
    "#1abc9c",   // Verde azulado
    "#f39c12",  // Naranja
    "#2ecc71",  // Verde claro
  ]
  ;
export function getRandomColors(size){
    let colors = [];
    for (let i = 0; i < size; i++) {
        //prevent duplicate colors
        let color = colorPalette[Math.floor(Math.random() * colorPalette.length)];
        if(colors.includes(color)){
            if(colorPalette.length <= colors.length){
                colors.push(color);
            }else{
                i--;
            }
        }else{
            colors.push(color);
        }
    }
    return colors;
}