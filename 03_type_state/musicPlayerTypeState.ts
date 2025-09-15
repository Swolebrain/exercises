// Type-state implementation of MusicPlayer
// States are encoded in the type system to prevent invalid operations


interface IMusicPlayerBase {
    currentTrackName(): string;
    addTrack(trackName: string, position: number): void;
    next(): void;
    jump(position: number): void;
}

interface IPlayingMusicPlayer extends IMusicPlayerBase {
    readonly _state: 'playing';
    playingId: number;
    pause(): IPausedMusicPlayer;
    
};
interface IPausedMusicPlayer extends IMusicPlayerBase {
    readonly _state: 'paused';
    play(): IPlayingMusicPlayer;
};

type MusicPlayer = IPlayingMusicPlayer | IPausedMusicPlayer;


export class PlayingMusicPlayer implements IPlayingMusicPlayer {
    readonly _state: 'playing' = 'playing';
    tracks: string[];
    playingId: number;
    constructor(tracks: string[], playingId: number) {
        this.tracks = tracks;
        this.playingId = playingId;
    }
    pause(): IPausedMusicPlayer {
        return new PausedMusicPlayer(this.tracks, this.playingId);
    }
    addTrack(trackName: string, position: number): PlayingMusicPlayer {
        const newTracks = [...this.tracks.slice(0, position), trackName, ...this.tracks.slice(position)];
        return new PlayingMusicPlayer(newTracks, this.playingId);
    }

    currentTrackName(): string {
        return this.tracks[this.playingId];
    }

    next(): PlayingMusicPlayer {
        let newPlayingId = (this.playingId + 1) % this.tracks.length;
        return new PlayingMusicPlayer(this.tracks, newPlayingId);
    }

    jump(position: number): PlayingMusicPlayer {
        return new PlayingMusicPlayer(this.tracks, position);
    }
}

export class PausedMusicPlayer implements IPausedMusicPlayer {
    readonly _state: 'paused' = 'paused';
    tracks: string[];
    lastPlayedId: number;
    constructor(tracks: string[], playingId: number) {
        this.tracks = tracks;
        this.lastPlayedId = playingId;
    }
    play(): IPlayingMusicPlayer {
        return new PlayingMusicPlayer(this.tracks, this.lastPlayedId);
    }
    addTrack(trackName: string, position: number): PausedMusicPlayer {
        const newTracks = [...this.tracks.slice(0, position), trackName, ...this.tracks.slice(position)];
        return new PausedMusicPlayer(newTracks, this.lastPlayedId);
    }
    currentTrackName(): string {
        return this.tracks[this.lastPlayedId];
    }
    next(): PausedMusicPlayer {
        let newPlayingId = (this.lastPlayedId + 1) % this.tracks.length;
        return new PausedMusicPlayer(this.tracks, newPlayingId);
    }
    jump(position: number): PausedMusicPlayer {
        return new PausedMusicPlayer(this.tracks, position);
    }
}



