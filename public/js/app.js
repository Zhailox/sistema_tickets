/**
 * Lógica interactiva del cliente para el Sistema de Atención y Soporte Técnico
 * Incluye preferencias persistentes de modo oscuro y escala tipográfica, menú lateral colapsable en escritorio
 * y cajón flotante con fondo oscuro en móvil, componente buscador autocompletable en tiempo real,
 * sistema de paginación reutilizable integrado con filtrado instantáneo, subida estilizada de archivos
 * y confirmaciones de seguridad.
 */

(function () {
    'use strict';

    const CLAVE_TEMA = 'tema_institucional';
    const CLAVE_LETRA = 'tamano_letra_institucional';
    const CLAVE_SIDEBAR = 'sidebar_institucional';

    /**
     * Escapa caracteres especiales HTML para prevenir inyecciones XSS en el DOM
     */
    function escaparHtml(valor) {
        if (valor === null || valor === undefined) return '';
        return String(valor)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    /**
     * Limpia un contenedor del DOM de forma segura sin utilizar innerHTML
     */
    function vaciarContenedorSeguro(elemento) {
        if (!elemento) return;
        while (elemento.firstChild) {
            elemento.removeChild(elemento.firstChild);
        }
    }

    /**
     * Normaliza texto quitando tildes y pasando a minúsculas para búsquedas insensibles a acentos
     */
    function normalizarBusqueda(texto) {
        if (!texto) return '';
        return String(texto)
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .trim();
    }

    /**
     * Aplica el tema visual guardado y sincroniza controles en la vista de perfil
     */
    function aplicarTemaVisual(tema) {
        const temaFinal = tema === 'oscuro' ? 'oscuro' : 'claro';
        document.documentElement.setAttribute('data-tema', temaFinal);
        try {
            localStorage.setItem(CLAVE_TEMA, temaFinal);
        } catch (err) {}

        document.querySelectorAll('[data-accion="alternar-tema"]').forEach(function (boton) {
            const etiqueta = boton.querySelector('.texto-boton-tema');
            const texto = temaFinal === 'oscuro' ? 'Activar modo claro' : 'Activar modo oscuro';
            if (etiqueta) {
                etiqueta.textContent = texto;
            } else {
                boton.textContent = texto;
            }
            boton.setAttribute('aria-pressed', temaFinal === 'oscuro' ? 'true' : 'false');
        });

        const switchTema = document.getElementById('interruptorModoOscuro');
        if (switchTema) {
            switchTema.checked = (temaFinal === 'oscuro');
        }

        const estadoTemaTexto = document.getElementById('estadoActualModoOscuro');
        if (estadoTemaTexto) {
            estadoTemaTexto.textContent = temaFinal === 'oscuro' ? 'Modo oscuro activado' : 'Modo claro activado';
        }

        window.dispatchEvent(new CustomEvent('cambioTemaInstitucional', { detail: { tema: temaFinal } }));
        document.dispatchEvent(new CustomEvent('cambioTemaInstitucional', { detail: { tema: temaFinal } }));
    }

    /**
     * Aplica el tamaño de fuente accesible guardado y sincroniza controles en la vista de perfil
     */
    function aplicarTamanoLetra(tamano) {
        const tamanoFinal = tamano === 'grande' ? 'grande' : 'normal';
        document.documentElement.setAttribute('data-letra', tamanoFinal);
        try {
            localStorage.setItem(CLAVE_LETRA, tamanoFinal);
        } catch (err) {}

        document.querySelectorAll('[data-accion="alternar-letra"]').forEach(function (boton) {
            const etiqueta = boton.querySelector('.texto-boton-letra');
            const texto = tamanoFinal === 'grande' ? 'Usar escala estándar' : 'Activar letra aumentada';
            if (etiqueta) {
                etiqueta.textContent = texto;
            } else {
                boton.textContent = texto;
            }
        });

        const switchLetra = document.getElementById('interruptorLetraGrande');
        if (switchLetra) {
            switchLetra.checked = (tamanoFinal === 'grande');
        }

        const estadoLetraTexto = document.getElementById('estadoActualLetraGrande');
        if (estadoLetraTexto) {
            estadoLetraTexto.textContent = tamanoFinal === 'grande' ? 'Vista aumentada activa' : 'Escala compacta estándar activa';
        }
    }

    /**
     * Aplica el estado colapsado o expandido del menú lateral en escritorio
     */
    function aplicarEstadoSidebar(estado) {
        const estadoFinal = estado === 'colapsado' ? 'colapsado' : 'expandido';
        document.documentElement.setAttribute('data-sidebar', estadoFinal);
        try {
            localStorage.setItem(CLAVE_SIDEBAR, estadoFinal);
        } catch (err) {}
    }

    /**
     * Controla la apertura y cierre del menú lateral como cajón flotante en móviles (< 992px)
     */
    function inicializarMenuLateralResponsivo() {
        const botonAlternar = document.getElementById('botonAlternarMenuLateral');
        const botonCerrarMovil = document.getElementById('botonCerrarMenuMovil');
        const menuLateral = document.getElementById('menuLateralInstitucional');
        const capaFondo = document.getElementById('capaFondoMenuMovil');

        if (!menuLateral) return;

        function abrirMenuMovil() {
            menuLateral.classList.add('abierto');
            if (capaFondo) capaFondo.classList.add('visible');
            document.body.classList.add('menu-movil-abierto');
        }

        function cerrarMenuMovil() {
            menuLateral.classList.remove('abierto');
            if (capaFondo) capaFondo.classList.remove('visible');
            document.body.classList.remove('menu-movil-abierto');
        }

        if (botonAlternar) {
            botonAlternar.addEventListener('click', function () {
                if (window.innerWidth < 992) {
                    if (menuLateral.classList.contains('abierto')) {
                        cerrarMenuMovil();
                    } else {
                        abrirMenuMovil();
                    }
                } else {
                    const actual = document.documentElement.getAttribute('data-sidebar') || 'expandido';
                    aplicarEstadoSidebar(actual === 'colapsado' ? 'expandido' : 'colapsado');
                }
            });
        }

        if (botonCerrarMovil) {
            botonCerrarMovil.addEventListener('click', cerrarMenuMovil);
        }

        if (capaFondo) {
            capaFondo.addEventListener('click', cerrarMenuMovil);
        }

        // Cerrar cajón móvil al tocar cualquier enlace de navegación
        menuLateral.querySelectorAll('.enlace-navegacion').forEach(function (enlace) {
            enlace.addEventListener('click', function () {
                if (window.innerWidth < 992) {
                    cerrarMenuMovil();
                }
            });
        });

        // Cerrar con tecla Escape o al redimensionar a escritorio
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && menuLateral.classList.contains('abierto')) {
                cerrarMenuMovil();
            }
        });

        window.addEventListener('resize', function () {
            if (window.innerWidth >= 992 && menuLateral.classList.contains('abierto')) {
                cerrarMenuMovil();
            }
        });
    }

    /**
     * Inicializa los controles de mostrar y ocultar contraseña
     */
    function inicializarBotonesVerClave() {
        const botones = document.querySelectorAll('[data-alternar-clave]');
        botones.forEach(function (boton) {
            boton.addEventListener('click', function () {
                const selectorDestino = boton.getAttribute('data-alternar-clave');
                const campo = document.querySelector(selectorDestino);
                if (!campo) return;
                if (campo.type === 'password') {
                    campo.type = 'text';
                    boton.textContent = 'Ocultar';
                } else {
                    campo.type = 'password';
                    boton.textContent = 'Mostrar';
                }
            });
        });
    }

    /**
     * Inicializa el medidor de fortaleza y coincidencia de contraseñas
     */
    function inicializarMedidorClave() {
        const campoNueva = document.querySelector('[data-medidor-clave]');
        const barra = document.getElementById('barraFortalezaClave');
        const texto = document.getElementById('textoFortalezaClave');
        const campoConfirmar = document.querySelector('[data-confirmar-clave]');
        const textoCoincidencia = document.getElementById('textoCoincidenciaClave');

        if (campoNueva && barra && texto) {
            campoNueva.addEventListener('input', function () {
                const valor = campoNueva.value || '';
                let puntaje = 0;

                if (valor.length >= 6) puntaje += 35;
                if (valor.length >= 10) puntaje += 25;
                if (/[A-Z]/.test(valor) && /[a-z]/.test(valor)) puntaje += 20;
                if (/[0-9]/.test(valor) || /[^A-Za-z0-9]/.test(valor)) puntaje += 20;

                if (valor.length === 0) {
                    barra.style.width = '0%';
                    barra.className = 'indicador-fortaleza-barra';
                    texto.textContent = 'Escribe al menos 6 caracteres.';
                } else if (valor.length < 6) {
                    barra.style.width = '25%';
                    barra.style.backgroundColor = '#DC2626';
                    texto.textContent = 'La contraseña aún es muy corta.';
                } else if (puntaje <= 55) {
                    barra.style.width = '55%';
                    barra.style.backgroundColor = '#D97706';
                    texto.textContent = 'Nivel de seguridad básico.';
                } else if (puntaje <= 80) {
                    barra.style.width = '80%';
                    barra.style.backgroundColor = '#0284C7';
                    texto.textContent = 'Nivel de seguridad recomendado.';
                } else {
                    barra.style.width = '100%';
                    barra.style.backgroundColor = '#16A34A';
                    texto.textContent = 'Nivel de seguridad óptimo.';
                }

                verificarCoincidencia();
            });
        }

        function verificarCoincidencia() {
            if (!campoNueva || !campoConfirmar || !textoCoincidencia) return;
            if (campoConfirmar.value.length === 0) {
                textoCoincidencia.textContent = '';
                textoCoincidencia.className = 'small mt-1 texto-secundario';
                return;
            }
            if (campoNueva.value === campoConfirmar.value) {
                textoCoincidencia.textContent = 'Las dos contraseñas coinciden correctamente.';
                textoCoincidencia.className = 'small mt-1 text-success fw-semibold';
            } else {
                textoCoincidencia.textContent = 'Las contraseñas escritas no coinciden.';
                textoCoincidencia.className = 'small mt-1 text-danger fw-semibold';
            }
        }

        if (campoConfirmar) {
            campoConfirmar.addEventListener('input', verificarCoincidencia);
        }
    }

    /**
     * Extrae todo el texto buscable de una fila de tabla incluyendo valores dentro de inputs y selects
     */
    function obtenerTextoFilaTabla(fila) {
        let base = fila.textContent || '';
        fila.querySelectorAll('input[type="text"], input[type="number"], input[type="search"]').forEach(function (inp) {
            if (inp.value) {
                base += ' ' + inp.value;
            }
        });
        fila.querySelectorAll('select').forEach(function (sel) {
            if (sel.selectedIndex >= 0 && sel.options[sel.selectedIndex]) {
                base += ' ' + sel.options[sel.selectedIndex].text;
            }
        });
        return normalizarBusqueda(base);
    }

    /**
     * Construye el sistema de paginación reutilizable e integrado con búsqueda en vivo para una tabla
     */
    function construirPaginacionTabla(tabla) {
        if (!tabla || tabla.dataset.paginacionInicializada === 'si') {
            return;
        }
        const cuerpo = tabla.querySelector('tbody');
        if (!cuerpo) return;

        tabla.dataset.paginacionInicializada = 'si';

        // Identificar filas reales de datos frente a filas de estado vacío inicial
        const todasLasFilas = Array.from(cuerpo.querySelectorAll('tr'));
        const filasDatos = [];
        let filaVaciaServidor = null;

        todasLasFilas.forEach(function (tr) {
            if (tr.classList.contains('fila-sin-resultados')) {
                return;
            }
            const celdas = tr.querySelectorAll('td');
            if (celdas.length === 1 && parseInt(celdas[0].getAttribute('colspan') || '1', 10) > 1) {
                filaVaciaServidor = tr;
            } else {
                tr._coincideBusqueda = true;
                filasDatos.push(tr);
            }
        });

        const estado = {
            paginaActual: 1,
            tamanoPagina: parseInt(tabla.getAttribute('data-tamano-pagina') || '10', 10) || 10,
            terminoBusqueda: ''
        };

        // Crear contenedor visual de barra de paginación inferior
        const barraPaginacion = document.createElement('div');
        barraPaginacion.className = 'barra-paginacion-tabla';

        const infoContador = document.createElement('div');
        infoContador.className = 'paginacion-info-contador';

        const controlesDerecha = document.createElement('div');
        controlesDerecha.className = 'paginacion-controles-derecha';

        const bloqueSelector = document.createElement('div');
        bloqueSelector.className = 'paginacion-selector-cantidad';

        const etiquetaMostrar = document.createElement('span');
        etiquetaMostrar.className = 'texto-secundario small';
        etiquetaMostrar.textContent = 'Mostrar';

        const selectorTamano = document.createElement('select');
        selectorTamano.className = 'form-select form-select-sm selector-tamano-pagina';
        selectorTamano.setAttribute('aria-label', 'Cantidad de registros por página');
        [10, 25, 50].forEach(function (cant) {
            const op = document.createElement('option');
            op.value = String(cant);
            op.textContent = cant + ' por página';
            if (cant === estado.tamanoPagina) {
                op.selected = true;
            }
            selectorTamano.appendChild(op);
        });

        bloqueSelector.appendChild(etiquetaMostrar);
        bloqueSelector.appendChild(selectorTamano);

        const grupoBotones = document.createElement('div');
        grupoBotones.className = 'grupo-botones-paginacion';

        controlesDerecha.appendChild(bloqueSelector);
        controlesDerecha.appendChild(grupoBotones);

        barraPaginacion.appendChild(infoContador);
        barraPaginacion.appendChild(controlesDerecha);

        // Insertar después del contenedor .table-responsive o de la propia tabla
        const contenedorResponsive = tabla.closest('.table-responsive');
        if (contenedorResponsive && contenedorResponsive.parentNode) {
            contenedorResponsive.parentNode.insertBefore(barraPaginacion, contenedorResponsive.nextSibling);
        } else if (tabla.parentNode) {
            tabla.parentNode.insertBefore(barraPaginacion, tabla.nextSibling);
        }

        function crearBotonPagina(texto, paginaDestino, deshabilitado, esActivo) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'btn-paginacion-item' + (esActivo ? ' activo' : '');
            btn.textContent = texto;
            btn.disabled = Boolean(deshabilitado);
            if (!deshabilitado && !esActivo) {
                btn.addEventListener('click', function () {
                    estado.paginaActual = paginaDestino;
                    renderizarEstadoTabla();
                });
            }
            return btn;
        }

        function obtenerRangoPaginas(actual, total) {
            if (total <= 7) {
                const arr = [];
                for (let i = 1; i <= total; i++) arr.push(i);
                return arr;
            }
            if (actual <= 4) {
                return [1, 2, 3, 4, 5, '...', total];
            }
            if (actual >= total - 3) {
                return [1, '...', total - 4, total - 3, total - 2, total - 1, total];
            }
            return [1, '...', actual - 1, actual, actual + 1, '...', total];
        }

        function renderizarEstadoTabla() {
            const filasCoincidentes = filasDatos.filter(function (f) {
                return f._coincideBusqueda !== false;
            });

            const totalCoincidentes = filasCoincidentes.length;
            const totalPaginas = Math.max(1, Math.ceil(totalCoincidentes / estado.tamanoPagina));

            if (estado.paginaActual > totalPaginas) {
                estado.paginaActual = totalPaginas;
            }
            if (estado.paginaActual < 1) {
                estado.paginaActual = 1;
            }

            const inicio = (estado.paginaActual - 1) * estado.tamanoPagina;
            const fin = Math.min(inicio + estado.tamanoPagina, totalCoincidentes);

            // Ocultar todas las filas de datos y mostrar únicamente las del segmento actual
            filasDatos.forEach(function (f) {
                f.style.display = 'none';
            });

            for (let i = inicio; i < fin; i++) {
                filasCoincidentes[i].style.display = '';
            }

            // Gestionar fila vacía cuando una búsqueda no arroja resultados
            let filaSinResultados = cuerpo.querySelector('.fila-sin-resultados');
            if (filasDatos.length > 0 && totalCoincidentes === 0) {
                if (!filaSinResultados) {
                    const totalColumnas = tabla.querySelectorAll('thead th').length || 5;
                    filaSinResultados = document.createElement('tr');
                    filaSinResultados.className = 'fila-sin-resultados';
                    const celda = document.createElement('td');
                    celda.colSpan = totalColumnas;
                    celda.className = 'text-center py-4 texto-secundario fw-semibold';
                    celda.textContent = 'No encontramos registros que coincidan con tu búsqueda.';
                    filaSinResultados.appendChild(celda);
                    cuerpo.appendChild(filaSinResultados);
                }
                filaSinResultados.style.display = '';
            } else if (filaSinResultados) {
                filaSinResultados.style.display = 'none';
            }

            if (filaVaciaServidor) {
                filaVaciaServidor.style.display = filasDatos.length === 0 ? '' : 'none';
            }

            // Actualizar contador en español natural
            if (totalCoincidentes === 0) {
                infoContador.textContent = 'Mostrando 0 a 0 de 0 registros';
            } else {
                infoContador.textContent = 'Mostrando ' + (inicio + 1) + ' a ' + fin + ' de ' + totalCoincidentes + ' registros';
            }

            // Construir controles de navegación Anterior / Páginas / Siguiente
            vaciarContenedorSeguro(grupoBotones);
            grupoBotones.appendChild(
                crearBotonPagina('Anterior', estado.paginaActual - 1, estado.paginaActual <= 1, false)
            );

            const paginasMostrar = obtenerRangoPaginas(estado.paginaActual, totalPaginas);
            paginasMostrar.forEach(function (p) {
                if (p === '...') {
                    const sep = document.createElement('span');
                    sep.className = 'paginacion-elipsis';
                    sep.textContent = '...';
                    grupoBotones.appendChild(sep);
                } else {
                    grupoBotones.appendChild(
                        crearBotonPagina(String(p), p, false, p === estado.paginaActual)
                    );
                }
            });

            grupoBotones.appendChild(
                crearBotonPagina('Siguiente', estado.paginaActual + 1, estado.paginaActual >= totalPaginas, false)
            );
        }

        selectorTamano.addEventListener('change', function () {
            estado.tamanoPagina = parseInt(selectorTamano.value, 10) || 10;
            estado.paginaActual = 1;
            renderizarEstadoTabla();
        });

        // Exponer método de filtrado para que el buscador instantáneo recalcule a la página 1
        tabla._filtrarPaginacion = function (termino) {
            const terminoNorm = normalizarBusqueda(termino || '');
            estado.terminoBusqueda = terminoNorm;
            filasDatos.forEach(function (fila) {
                if (terminoNorm === '') {
                    fila._coincideBusqueda = true;
                } else {
                    const contenido = obtenerTextoFilaTabla(fila);
                    fila._coincideBusqueda = (contenido.indexOf(terminoNorm) !== -1);
                }
            });
            estado.paginaActual = 1;
            renderizarEstadoTabla();
        };

        renderizarEstadoTabla();
    }

    /**
     * Inicializa el sistema de paginación y buscadores instantáneos en todas las tablas del sistema
     */
    function inicializarPaginacionYBusquedaTablas() {
        // Primero inicializar todas las tablas marcadas con data-paginacion="true"
        document.querySelectorAll('table[data-paginacion="true"]').forEach(function (tabla) {
            construirPaginacionTabla(tabla);
        });

        // Luego vincular todos los buscadores instantáneos data-buscador-tabla
        const buscadores = document.querySelectorAll('[data-buscador-tabla]');
        buscadores.forEach(function (input) {
            const selectorTabla = input.getAttribute('data-buscador-tabla');
            const tabla = document.querySelector(selectorTabla);
            if (!tabla) return;

            // Garantizar que toda tabla con buscador tenga también su paginación activa
            construirPaginacionTabla(tabla);

            input.addEventListener('input', function () {
                if (typeof tabla._filtrarPaginacion === 'function') {
                    tabla._filtrarPaginacion(input.value || '');
                }
            });
        });
    }

    /**
     * Componente Buscador Autocompletable en Tiempo Real (Reemplaza <select> masivos)
     * Convierte cualquier <select data-autocompletable="true"> en un buscador interactivo de 2 líneas
     * que guarda el valor seleccionado en un <input type="hidden">.
     */
    function construirBuscadorAutocompletable(selectOriginal) {
        if (!selectOriginal || selectOriginal.dataset.autocompletableInicializado === 'si') {
            return;
        }
        selectOriginal.dataset.autocompletableInicializado = 'si';

        const nombreCampo = selectOriginal.getAttribute('name') || '';
        const placeholder = selectOriginal.getAttribute('data-placeholder') || 'Escribe para buscar y seleccionar...';
        const esRequerido = selectOriginal.hasAttribute('required');

        // El select original pierde el atributo name para que el formulario envíe el input hidden
        selectOriginal.removeAttribute('name');
        selectOriginal.removeAttribute('required');
        selectOriginal.style.display = 'none';

        const contenedor = document.createElement('div');
        contenedor.className = 'buscador-autocompletable';

        const inputOculto = document.createElement('input');
        inputOculto.type = 'hidden';
        inputOculto.name = nombreCampo;
        inputOculto.value = selectOriginal.value || '';

        const controlWrap = document.createElement('div');
        controlWrap.className = 'buscador-autocompletable-control';

        const inputTexto = document.createElement('input');
        inputTexto.type = 'text';
        inputTexto.className = 'form-control buscador-autocompletable-input';
        inputTexto.placeholder = placeholder;
        inputTexto.autocomplete = 'off';
        if (esRequerido) {
            inputTexto.required = true;
        }

        const botonLimpiar = document.createElement('button');
        botonLimpiar.type = 'button';
        botonLimpiar.className = 'buscador-autocompletable-limpiar';
        botonLimpiar.title = 'Limpiar selección';
        botonLimpiar.textContent = '×';

        const iconoFlecha = document.createElement('span');
        iconoFlecha.className = 'buscador-autocompletable-icono';
        iconoFlecha.textContent = '▾';

        const menuFlotante = document.createElement('div');
        menuFlotante.className = 'buscador-autocompletable-menu';

        controlWrap.appendChild(inputTexto);
        controlWrap.appendChild(botonLimpiar);
        controlWrap.appendChild(iconoFlecha);
        contenedor.appendChild(inputOculto);
        contenedor.appendChild(controlWrap);
        contenedor.appendChild(menuFlotante);

        selectOriginal.parentNode.insertBefore(contenedor, selectOriginal.nextSibling);

        function obtenerOpcionesValidas() {
            const lista = [];
            Array.from(selectOriginal.options).forEach(function (opt) {
                if (opt.disabled && opt.value === '') return;
                if (opt.style.display === 'none' || opt.hidden) return;
                const subtexto = (opt.getAttribute('data-subtexto') || opt.getAttribute('data-secundario') || '').trim();
                lista.push({
                    valor: opt.value,
                    titulo: (opt.getAttribute('data-titulo') || opt.textContent || '').trim(),
                    subtitulo: subtexto,
                    busqueda: normalizarBusqueda(
                        (opt.getAttribute('data-titulo') || opt.textContent || '') + ' ' +
                        subtexto + ' ' +
                        (opt.getAttribute('data-busqueda') || '')
                    ),
                    opcionDom: opt
                });
            });
            return lista;
        }

        function sincronizarDesdeSelect() {
            const valorActual = selectOriginal.value || '';
            inputOculto.value = valorActual;
            const opcionSel = Array.from(selectOriginal.options).find(function (o) {
                return o.value === valorActual && valorActual !== '';
            });
            if (opcionSel) {
                inputTexto.value = (opcionSel.getAttribute('data-titulo') || opcionSel.textContent || '').trim();
                botonLimpiar.style.display = 'inline-block';
            } else {
                inputTexto.value = '';
                botonLimpiar.style.display = 'none';
            }
        }

        function seleccionarValor(valor, dispararEvento) {
            selectOriginal.value = valor;
            inputOculto.value = valor;
            sincronizarDesdeSelect();
            menuFlotante.classList.remove('abierto');
            if (dispararEvento) {
                selectOriginal.dispatchEvent(new Event('change', { bubbles: true }));
                inputOculto.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }

        function renderizarMenu(filtroTexto) {
            vaciarContenedorSeguro(menuFlotante);
            const termino = normalizarBusqueda(filtroTexto);
            const opciones = obtenerOpcionesValidas();
            let coincidencias = 0;

            opciones.forEach(function (item) {
                if (item.valor === '' && termino !== '') {
                    return;
                }
                if (termino !== '' && item.busqueda.indexOf(termino) === -1) {
                    return;
                }
                coincidencias++;

                const divOpcion = document.createElement('div');
                divOpcion.className = 'buscador-autocompletable-opcion' + (item.valor === inputOculto.value ? ' seleccionada' : '');

                const divTitulo = document.createElement('div');
                divTitulo.className = 'buscador-opcion-titulo';
                divTitulo.textContent = item.titulo;
                divOpcion.appendChild(divTitulo);

                if (item.subtitulo !== '') {
                    const divSub = document.createElement('div');
                    divSub.className = 'buscador-opcion-subtitulo';
                    divSub.textContent = item.subtitulo;
                    divOpcion.appendChild(divSub);
                }

                divOpcion.addEventListener('mousedown', function (e) {
                    e.preventDefault();
                    seleccionarValor(item.valor, true);
                });

                menuFlotante.appendChild(divOpcion);
            });

            if (coincidencias === 0) {
                const vacio = document.createElement('div');
                vacio.className = 'buscador-autocompletable-vacio';
                vacio.textContent = 'Sin coincidencias para tu búsqueda.';
                menuFlotante.appendChild(vacio);
            }
        }

        inputTexto.addEventListener('focus', function () {
            renderizarMenu('');
            menuFlotante.classList.add('abierto');
            inputTexto.select();
        });

        inputTexto.addEventListener('click', function () {
            if (!menuFlotante.classList.contains('abierto')) {
                renderizarMenu('');
                menuFlotante.classList.add('abierto');
            }
        });

        inputTexto.addEventListener('input', function () {
            renderizarMenu(inputTexto.value);
            menuFlotante.classList.add('abierto');
            if (inputTexto.value.trim() === '') {
                inputOculto.value = '';
                selectOriginal.value = '';
                botonLimpiar.style.display = 'none';
            }
        });

        inputTexto.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                menuFlotante.classList.remove('abierto');
                sincronizarDesdeSelect();
            } else if (e.key === 'Enter' && menuFlotante.classList.contains('abierto')) {
                const primeraOpcion = menuFlotante.querySelector('.buscador-autocompletable-opcion');
                if (primeraOpcion) {
                    e.preventDefault();
                    primeraOpcion.dispatchEvent(new MouseEvent('mousedown'));
                }
            }
        });

        inputTexto.addEventListener('blur', function () {
            setTimeout(function () {
                menuFlotante.classList.remove('abierto');
                sincronizarDesdeSelect();
            }, 160);
        });

        botonLimpiar.addEventListener('click', function (e) {
            e.preventDefault();
            seleccionarValor('', true);
            inputTexto.focus();
        });

        sincronizarDesdeSelect();

        // Guardar referencia para refresco externo cuando cambian opciones dinámicamente
        selectOriginal._sincronizarAutocompletable = sincronizarDesdeSelect;
    }

    function inicializarBuscadoresAutocompletables() {
        document.querySelectorAll('select[data-autocompletable="true"]').forEach(function (sel) {
            construirBuscadorAutocompletable(sel);
        });
    }

    window.refrescarBuscadorAutocompletable = function (selectElemento) {
        if (selectElemento && typeof selectElemento._sincronizarAutocompletable === 'function') {
            selectElemento._sincronizarAutocompletable();
        }
    };

    /**
     * Inicializa botones estilizados para adjuntar archivos ocultando el input file nativo
     */
    function inicializarSubidaArchivosEstilizada() {
        document.querySelectorAll('.contenedor-archivo-estilizado').forEach(function (contenedor) {
            const inputArchivo = contenedor.querySelector('.input-archivo-oculto');
            const etiquetaNombre = contenedor.querySelector('.etiqueta-archivo-seleccionado');
            const spanTexto = contenedor.querySelector('.nombre-archivo-truncado');
            const botonQuitar = contenedor.querySelector('.boton-quitar-archivo');

            if (!inputArchivo) return;

            inputArchivo.addEventListener('change', function () {
                const archivo = inputArchivo.files && inputArchivo.files[0];
                if (archivo) {
                    if (spanTexto) spanTexto.textContent = archivo.name;
                    if (etiquetaNombre) etiquetaNombre.classList.remove('d-none');
                } else {
                    if (spanTexto) spanTexto.textContent = '';
                    if (etiquetaNombre) etiquetaNombre.classList.add('d-none');
                }
            });

            if (botonQuitar) {
                botonQuitar.addEventListener('click', function (e) {
                    e.preventDefault();
                    inputArchivo.value = '';
                    if (spanTexto) spanTexto.textContent = '';
                    if (etiquetaNombre) etiquetaNombre.classList.add('d-none');
                });
            }
        });
    }

    /**
     * Inicializa el llenado rápido de usuarios de prueba en la pantalla de inicio de sesión
     */
    function inicializarAccesoRapidoDemostracion() {
        const botonesDemo = document.querySelectorAll('[data-llenar-usuario]');
        if (botonesDemo.length === 0) return;
        const campoUsuario = document.getElementById('identificador_acceso');
        const campoClave = document.getElementById('clave_acceso');

        botonesDemo.forEach(function (boton) {
            boton.addEventListener('click', function () {
                const usuario = boton.getAttribute('data-llenar-usuario') || '';
                const clave = boton.getAttribute('data-llenar-clave') || '123456';
                if (campoUsuario) {
                    campoUsuario.value = usuario;
                    campoUsuario.focus();
                }
                if (campoClave) {
                    campoClave.value = clave;
                }
            });
        });
    }

    /**
     * Inicializa navegación táctil rápida entre columnas del tablero en pantallas móviles
     */
    function inicializarSaltoColumnasMovil() {
        document.querySelectorAll('[data-ir-columna]').forEach(function (boton) {
            boton.addEventListener('click', function () {
                const selector = boton.getAttribute('data-ir-columna');
                const col = document.querySelector(selector);
                if (col) {
                    col.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
                }
            });
        });
    }

    /**
     * Inicializa confirmaciones antes de ejecutar acciones importantes
     */
    function inicializarConfirmaciones() {
        const elementosConfirmar = document.querySelectorAll('[data-confirmar-mensaje]');
        const modalElemento = document.getElementById('modalConfirmacionInstitucional');
        const textoModal = document.getElementById('textoModalConfirmacion');
        const botonAceptarModal = document.getElementById('botonConfirmarAccionModal');

        let accionPendiente = null;
        let instanciaModal = null;

        if (modalElemento && window.bootstrap && window.bootstrap.Modal) {
            instanciaModal = new window.bootstrap.Modal(modalElemento);
        }

        if (botonAceptarModal) {
            botonAceptarModal.addEventListener('click', function () {
                if (typeof accionPendiente === 'function') {
                    const ejecutar = accionPendiente;
                    accionPendiente = null;
                    if (instanciaModal) instanciaModal.hide();
                    ejecutar();
                }
            });
        }

        elementosConfirmar.forEach(function (el) {
            if (el.tagName === 'FORM') {
                el.addEventListener('submit', function (evento) {
                    if (el.getAttribute('data-confirmado') === 'si') return;
                    evento.preventDefault();
                    const mensaje = el.getAttribute('data-confirmar-mensaje') || '¿Deseas continuar con esta acción?';

                    if (instanciaModal && textoModal) {
                        textoModal.textContent = mensaje;
                        accionPendiente = function () {
                            el.setAttribute('data-confirmado', 'si');
                            el.submit();
                        };
                        instanciaModal.show();
                    } else if (window.confirm(mensaje)) {
                        el.setAttribute('data-confirmado', 'si');
                        el.submit();
                    }
                });
            } else if (el.tagName === 'A') {
                el.addEventListener('click', function (evento) {
                    evento.preventDefault();
                    const destino = el.getAttribute('href');
                    const mensaje = el.getAttribute('data-confirmar-mensaje') || '¿Deseas continuar con esta acción?';

                    if (instanciaModal && textoModal && destino) {
                        textoModal.textContent = mensaje;
                        accionPendiente = function () {
                            window.location.href = destino;
                        };
                        instanciaModal.show();
                    } else if (destino && window.confirm(mensaje)) {
                        window.location.href = destino;
                    }
                });
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        let temaGuardado = 'claro';
        let letraGuardada = 'normal';
        let sidebarGuardado = 'expandido';
        try {
            temaGuardado = localStorage.getItem(CLAVE_TEMA) || 'claro';
            letraGuardada = localStorage.getItem(CLAVE_LETRA) || 'normal';
            sidebarGuardado = localStorage.getItem(CLAVE_SIDEBAR) || 'expandido';
        } catch (err) {}

        aplicarTemaVisual(temaGuardado);
        aplicarTamanoLetra(letraGuardada);
        aplicarEstadoSidebar(sidebarGuardado);

        document.querySelectorAll('[data-accion="alternar-tema"]').forEach(function (boton) {
            boton.addEventListener('click', function () {
                const actual = document.documentElement.getAttribute('data-tema') || 'claro';
                aplicarTemaVisual(actual === 'oscuro' ? 'claro' : 'oscuro');
            });
        });

        document.querySelectorAll('[data-accion="alternar-letra"]').forEach(function (boton) {
            boton.addEventListener('click', function () {
                const actual = document.documentElement.getAttribute('data-letra') || 'normal';
                aplicarTamanoLetra(actual === 'grande' ? 'normal' : 'grande');
            });
        });

        const switchTema = document.getElementById('interruptorModoOscuro');
        if (switchTema) {
            switchTema.addEventListener('change', function () {
                aplicarTemaVisual(switchTema.checked ? 'oscuro' : 'claro');
            });
        }

        const switchLetra = document.getElementById('interruptorLetraGrande');
        if (switchLetra) {
            switchLetra.addEventListener('change', function () {
                aplicarTamanoLetra(switchLetra.checked ? 'grande' : 'normal');
            });
        }

        inicializarMenuLateralResponsivo();
        inicializarBotonesVerClave();
        inicializarMedidorClave();
        inicializarPaginacionYBusquedaTablas();
        inicializarBuscadoresAutocompletables();
        inicializarSubidaArchivosEstilizada();
        inicializarAccesoRapidoDemostracion();
        inicializarSaltoColumnasMovil();
        inicializarConfirmaciones();
    });
})();
