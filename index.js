//incluir modulo dotenv
require('dotenv').config();
const { Console } = require('console');
//importar paquete express
const express = require('express');
const bp = require("body-parser");
const QRCode = require("qrcode");
const fs = require('fs');

//inicializar express
const app = express();
//crear un servidor para socket io
const server = require('https').createServer({
                                                key: fs.readFileSync('my_cert.key'),
                                                cert: fs.readFileSync('my_cert.crt')
                                            }, app); 

//incluir morgan para que se puedan ver las peticiones http
const morgan = require('morgan');
//paquete para gestionar directorios
var path = require('path');
//PAQUETE PARA MANEJAR LOS CORS
var cors = require('cors')
//paquetes para los cron
var cron = require('node-cron');
// Incluir configuraciones del proyecto
const config = require(path.resolve('./', 'config.js'));

//pasar el servidor a socket io
const io = require('socket.io')(server, {
    cors: {
        origin: "*",
        methods: ["GET", "POST"]
    }
});

//VARIABLE GLOBAL PARA QUE ALMACENE EL TOCKEN
const SESSION_FILE_PATH_GPS = './AdminSessionGps.json';
const SESSION_FILE_PATH_DISPOSITIVOS_GPS = './DispositivosGps.json';
//SECION GPS
sessionGPS = require(SESSION_FILE_PATH_GPS);
dispositivosGps = require(SESSION_FILE_PATH_DISPOSITIVOS_GPS);

//INCLUIR LOS CORS
app.use(cors());
//INCLUIR MIDDLEWARES
app.use(morgan('dev'));
app.use(express.urlencoded({ extended: false }));
app.use(express.json());

app.set("view engine", "ejs");
app.use(bp.urlencoded({ extended: false }));
app.use(bp.json());

//const { Client, Location, List, Buttons,LocalAuth } = require('whatsapp-web.js');
const { Client, Location, List, Buttons, LocalAuth } = require('./index2');

const SESSION_FILE_PATH = './session.json';
let sessionCfg;
let qr_imagen = null;
let CLIENTE_CONECTADO = null;

//validar si ya existe una seccion activa
if (fs.existsSync(SESSION_FILE_PATH)) {
    //si existe se le envia el parametro
    sessionCfg = require(SESSION_FILE_PATH);
}

//================================
//================================
//          ADMINISTRACION 
//                DE 
//            MENSAJERIA DE WS
//=================================
//=================================
//inicializamos el cliente de whasap wb
const client = new Client({
    authStrategy: new LocalAuth(),
    puppeteer: { headless: true }
});

let QR_CODIGO = "";
client.initialize();



//lanzamos el QR para sincronizar
client.on('qr', async (qr) => {
    // NOTE: This event will not be fired if a session is specified.
    console.log('nuevo qr generado')
    qr_imagen = await convertirQR(qr);

    manejarSalaPorConfiguracionesWS('envio-qr', { 'qr_imagen': qr_imagen, 'cliente_conectado': CLIENTE_CONECTADO })

});


//funcion que se activa al iniciar seccion con el modo wasap web
client.on('authenticated', () => {
    console.log('AUTHENTICATED');
    WS_CONEXION = true;
    /* sessionCfg = session;
    //se guarda la secion
    fs.writeFile(SESSION_FILE_PATH, JSON.stringify(session), function (err) {
        if (err) {
            console.error(err);
        }
    }); */
});


client.on('auth_failure', msg => {
    // Fired if session restore was unsuccessfull
    console.error('AUTHENTICATION FAILURE', msg);
});

client.on('ready', () => {
    CLIENTE_CONECTADO = true;

    manejarSalaPorConfiguracionesWS('envio-qr', { 'qr_imagen': qr_imagen, 'cliente_conectado': CLIENTE_CONECTADO })

});


client.on('disconnected', (reason) => {

    let limpiar_seccion = {
        "WABrowserId": "",
        "WASecretBundle": "",
        "WAToken1": "",
        "WAToken2": ""
    };

    fs.writeFile(SESSION_FILE_PATH, JSON.stringify(limpiar_seccion), function (err) {
        if (err) {
            console.error(err);
        }
    });

    CLIENTE_CONECTADO = null

    client.initialize();

});


/**
 * funcion encargada de manejar 
 * los eventos de la sala de configuraciones
 * @param {*} evento 
 * @param {*} data 
 */
const manejarSalaPorConfiguracionesWS = (evento, data) => {

    salaPorConfiguracionesWS.to(`config`).emit(evento, data);

}

function convertirQR(qr) {

    return new Promise((resolve, reject) => {

        QRCode.toDataURL(qr, (err, imagen) => {
            if (err) res.send("Error occured");

            qr_imagen = imagen


            // Let us return the QR code image as our response and set it to be the source used in the webpage
            resolve(imagen);
        });

    })


}


// CREANDO NAMESPACE PARA LAS SALAS DE SOCKET
const salaPorConfiguracionesWS = io.of("/configuracion-ws");
//====================================
//  SALA PARA LAS configuraciones
//====================================
salaPorConfiguracionesWS.on('connection', (socket) => {

    console.log('se unio un nuevo cliente desde la web');

    //suscribir al cliente con el numero de extencion
    socket.on('suscribir', () => {
        socket.join(`config`);
        //respuesta de la suscripcion
        socket.emit('resultado-suscripcion', {
            ok: true,
            mensaje: `suscripción exitosa con las configuraciones`
        });

    });

    //desuscribirse al cliente con el numero de extencion
    socket.on('desuscribirse', () => {
        socket.leave(`config`)

        //respuesta de la suscripcion
        socket.emit('resultado-desuscripcion', {
            ok: true,
            mensaje: `saliste de la sala: config`
        });
    });

    //desuscribirse al cliente con el numero de extencion
    socket.on('solicitar-qr', () => {

        //respuesta de la suscripcion
        manejarSalaPorConfiguracionesWS('envio-qr', { 'qr_imagen': qr_imagen, 'cliente_conectado': CLIENTE_CONECTADO })

    });

});


//================================
//================================
//             RUTAS 
//            EXPRESS 
//     PARA ENVIO DE MENSAJES
//=================================
//=================================

 
//api res para resibir los estados mensajes de ws
app.post('/enviar-mensaje', function (req, res) {

    //obtener parametros
    let parametros = req.body;
    
    if (CLIENTE_CONECTADO) {
        
        client.sendMessage(`${parametros.numero}@c.us`, parametros.mensaje);
    }

    res.sendStatus(200);

});

app.get('/prueba',function (req, res) {
    
    res.sendStatus(200); 
})


//levantar servidor en el puerto requerido
server.listen(process.env.PORT,process.env.HOST, () => {

    console.log(`SERVIDOR HTTP ESCUCHANDO EN: ${process.env.HOST}:${process.env.PORT}`);
});

//================================
//================================
//           WEB SOCKET   
//     PARA COMUNICACION CON LA WEB
//=================================
//=================================

// CREANDO NAMESPACE PARA LAS SALAS DE SOCKET
const salaGps = io.of("/configuracion-gps");

//=============================================
//  SALA PARA CONSULTAS RELACIONADAS A LOS gps
//=============================================

salaGps.on('connection', (socket) => {

    //suscribir al cliente con el numero de extencion
    socket.on('suscribir', () => {
        socket.join(`configuracion-gps`);
        //respuesta de la suscripcion
        socket.emit('resultado-suscripcion', {
            ok: true,
            mensaje: `suscripción exitosa con la numero`
        });

    });

    //EVENTO PARA ENVIAR UN MENSAJE DE ws
    socket.on('consultar-estatus-plataforma-gps', () => {
        //accion a realizar al enviar un mensaje    
        //enviar mensaje 
        socket.emit('estatus-plataforma-gps', {
            ok: true,
            sessionGPS,
            dispositivosGps,
        });
    });

    //EVENTO PARA ENVIAR UN MENSAJE DE ws
    socket.on('actualizar-ubicaciones-gps', () => {
        //accion a realizar 
        //actualizarUbicaciones()
        //enviar mensaje 
        socket.emit('estatus-plataforma-gps', {
            ok: true,
            sessionGPS,
            dispositivosGps,
        });
    });

    //desuscribirse al cliente con el numero de extencion
    socket.on('desuscribirse', () => {
        socket.leave(`configuracion-gps`)

        //respuesta de la suscripcion
        socket.emit('resultado-desuscripcion', {
            ok: true,
            mensaje: `saliste de la sala: configuracion-gps`
        });
    });


});


/**
 * funcion encargada de manejar 
 * los eventos de la sala de configuraciones
 * @param {*} evento 
 * @param {*} data 
 */
const manejarSalaPorConfiguracionesGps = (evento, data) => {

    salaGps.to(`configuracion-gps`).emit(evento, data);

}


//ejecutar cron cada min
cron.schedule('* * * * *', async () => {
    
    console.log('actualizarUbicaciones')
    actualizarUbicaciones();

    //restarle 1 minuto al tocken
    sessionGPS.TIEMPO_TOCKEN_GPS = sessionGPS.TIEMPO_TOCKEN_GPS - 1;
    
    //cambio en tiempo de seccion informar al socket
    manejarSalaPorConfiguracionesGps('cambio-en-seccion', { sessionGPS, dispositivosGps })

    //validar si el token le quedan mas de 5 minutos de actividad
    if (sessionGPS.TIEMPO_TOCKEN_GPS > 5) {

        return
    }

    //refrescar el tocken por que ya le quedan menos de 5 minutos de actividad
    let nuevo_tocken = await RefrescarTockenGps();

    //actualizar variables
    actualizarVariablesTocken(nuevo_tocken);

        
        
});


//actualizar ubicaciones
async function actualizarUbicaciones() {

    console.log(Object.keys(dispositivosGps.items).length)
    if (Object.keys(dispositivosGps.items).length !== 0) {
         
        //obtener todas las ubicaciones    
        dispositivosGps.items.map(async (dispositivo) => {
            if (dispositivo.vehiculo.ultima_ubicacion) {
                
                dispositivo.vehiculo.ultima_ubicacion = await consumirRutaApiGpsGet(`/users/${sessionGPS.USUARIO_ID}/devices/${dispositivo.id}/last-location`)
            }
        })
    }
 
}

inicializarTocken();

/**
 * metodo encargado de refrezcar token gps
 * @param {*}  
 */
function CrearTockenGps() {

    let params = {
        "username": process.env.API_GPS_USUARIO,
        "password": process.env.API_GPS_CLAVE,
        "realm": process.env.API_GPS_REALM
    }


    return new Promise((resolve, reject) => {

        config.API_GPS.post('auth/token', params)
            .then(function (response) {
                console.log(response.data)
                resolve(response.data);

            })
            .catch(function (error) {
                 
                reject(error.response);
            });


    });
}

/**
 * metodo encargado de refrezcar token gps
 * @param {*}  
 */
function RefrescarTockenGps() {

    return new Promise((resolve, reject) => {

        config.API_GPS.post('auth/refreshtoken', { 'expiredAccessToken': TOCKEN_GPS })
            .then(function (response) {
                //console.log(response.data)
                resolve(response.data);

            })
            .catch(function (error) {
                
                reject(error.response);
            });


    });
}

/**
 * cambiar las variables globales
 * @param {*} tocken 
 */
function actualizarVariablesTocken(tocken) {

    //actualizar datos del tocken
    sessionGPS.TOCKEN_GPS = tocken.accessToken;
    sessionGPS.TIEMPO_TOCKEN_GPS = tocken.expiresIn;

}

async function inicializarTocken() {

    let data_tocken = await CrearTockenGps();

    //actualizar variables
    actualizarVariablesTocken(data_tocken);

    let iniciar = await iniciarSecionGps();

    //agregar usuario y cliente al json global
    sessionGPS.CLIENTE_ID = iniciar.clientId;
    sessionGPS.USUARIO_ID = iniciar.userId;

    //console.log(sessionGPS)
    //obtener los dispositivos gps
    let dispositivos = await consumirRutaApiGpsGet(`/users/${sessionGPS.USUARIO_ID}/devices`);

    //actualizar json de dispositivos
    dispositivosGps = dispositivos;

    //obtener todos los vehiculos
    await Promise.all(
        dispositivosGps.items.map(async (gps) => {
            gps.vehiculo = await consumirRutaApiGpsGet(`/automotors/${gps.id}`)
        })
    )

    //obtener todas las ubicaciones    
    await Promise.all(
        dispositivosGps.items.map(async (dispositivo) => {
            dispositivo.vehiculo.ultima_ubicacion = await consumirRutaApiGpsGet(`/users/${sessionGPS.USUARIO_ID}/devices/${dispositivo.id}/last-location`)
        })
    )

}

/**
 * metodo encargado de iniciar secion gps
*/
function iniciarSecionGps() {

    let params = {
        "username": process.env.WEB_GPS_USUARIO,
        "password": process.env.WEB_GPS_CLAVE,
        "subdomain": process.env.WEB_GPS_SUB_DOMINIO
    };

    return new Promise((resolve, reject) => {

        config.API_GPS.post('sessions', params, {
            headers: {
                domain: process.env.WEB_GPS_DOMINIO,
                authorization: `bearer ${sessionGPS.TOCKEN_GPS}`
            }
        }).then(function (response) {
            //console.log(response.data)
            resolve(response.data);

        })
            .catch(function (error) {
                
                reject(error.response);
            });


    });
}

/**
 * metodo encargado de obtener todos los dispositivos GPS
*/

function consumirRutaApiGpsGet(url) {

    return new Promise((resolve, reject) => {

        config.API_GPS.get(url, {
            headers: {
                domain: process.env.WEB_GPS_DOMINIO,
                authorization: `bearer ${sessionGPS.TOCKEN_GPS}`
            }
        }).then(function (response) {

            resolve(response.data);

        })
            .catch(function (error) {
                
                reject(error.response);
            });


    });

}

