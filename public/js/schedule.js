
document.addEventListener('DOMContentLoaded', function() {
    const scheduleEl = document.getElementById('schedule');
    
    
    // Récupérer la chaîne JSON de l'attribut data-formatted-events
    const formattedEventsString = document.getElementById('schedule').getAttribute('data-formatted-events');

    // récupérer sous forme de tableau:
    const formattedEvents = JSON.parse(formattedEventsString);

    // formatter les dates 
    formattedEvents.forEach(event => {
        event.start = new Date(event.start);
        event.end = new Date(event.end);
        event.id = event.slug;
        event.type = event.type;
        
        });

    const schedule = new FullCalendar.Calendar(scheduleEl, {
        initialView: 'dayGridMonth',
        events: formattedEvents,
        dayMaxEventRows: 1,

        eventContent: function(arg) {
            return {
                html: arg.event.title
            };
        },

        // eventTextColor: 'black', 
        eventClassNames: function(arg) {
            // Obtenir le type de l'événement de l'objet arg (argument)
            const eventType = arg.event.extendedProps.type;
            // Définir la classe CSS en fonction du type d'événement
            let classNames = [];
            if (eventType === 'EVENT') {
                classNames.push('event-class'); // Ajouter la classe pour les événements
            } else if (eventType === 'EXPO') {
                classNames.push('expo-class'); // Ajouter la classe pour les expositions
            }
            return classNames; // Retourner le tableau de noms de classe
        },

        eventClick: function(info) {
            
            window.location.href = '/event/' + info.event.id;
        },
        timeZone: 'UTC',
        header: {
            left: '',
            center: '',
            right: ''
        }
   
    });
    
schedule.render();
});